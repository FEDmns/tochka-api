<?php


namespace FEDmns\TochkaAPI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


/**
 * Class TochkaAPIAuth
 * @package FEDmns\TochkaAPI
 */
class TochkaAPIAuth
{

    /**
     * @var string
     */
    private string $client_id = '';
    /**
     * @var string
     */
    private string $client_secret = '';

    /**
     * @var string
     */
    private string $grant_type_cl = 'client_credentials';
    /**
     * @var string
     */
    private string $grant_type_au = 'authorization_code';
    /**
     * @var string
     */
    private string $grant_type_re = 'refresh_token';
    /**
     * @var string
     */
    private string $scope = 'accounts cards customers sbp payments';
    /**
     * @var string
     */
    private string $state = 'qwe';

    /**
     * @var bool|string
     */
    private bool|string $access_token = '';
    /**
     * @var bool|string
     */
    private bool|string $refresh_token = '';
    /**
     * @var bool|string
     */
    private bool|string $consentId = '' ;

    /**
     * TochkaAPIAuth constructor.
     * @param $client_id
     * @param $client_secret
     * @param $redirect_uri
     */
    public function __construct($client_id, $client_secret, $redirect_uri) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_uri = $redirect_uri;
    }

    /**
     *  Начало авторизации по oAuth
     *
     * @return false|string Url для перехода в банк для подтверждения разрешений. После чего доступен Code
     */
    public function oAuth()
    {
        $this->receiveAccessToken();
        if ($this->access_token) {
            $this->createConsent();
        }

        if ($this->consentId) {
            return $this->getConfirmConsentUrl();
        } else {
            return false;
        }
    }

    /**
     *  oAuth авторизацию по принципу client_credentials
     *
     */
    private function receiveAccessToken()
    {
        $param = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $this->grant_type_cl,
            'scope' => $this->scope,
            'state' => $this->state
        ];
        $response = Http::asForm()->post('https://enter.tochka.com/connect/token', $param);
        $data = json_decode($response);

        if (isset($data->access_token)) {
            $this->access_token = $data->access_token;
        } else {
            Log::error('Не удалось получить access_token для client_id: '.$this->client_id);
            Log::error(json_encode($data));
            $this->access_token = false;
        }
    }

    /**
     *  Создать список разрешений
     *
     * @return false|string Возвращает consentId для использования в getConfirmConsentUrl
     */
    private function createConsent()
    {
        $param = ["Data" => [
                    "permissions" => [
                        "ReadAccountsBasic",
                        "ReadAccountsDetail",
                        "ReadBalances",
                        "ReadStatements",
                        "ReadTransactionsBasic",
                        "ReadTransactionsCredits",
                        "ReadTransactionsDebits",
                        "ReadTransactionsDetail",
                        "ReadCustomerData",
                        "ReadSBPData",
                        "EditSBPData",
                        "ReadCardData",
                        "EditCardData",
                        "EditCardState",
                        "ReadCardLimits",
                        "EditCardLimits",
                        "CreatePaymentForSign",
                        "CreatePaymentOrder"
                    ],
                    "expirationDateTime" => "2040-10-03T00:00:00+00:00"]
                  ];

        $response = Http::withToken($this->access_token)->post('https://enter.tochka.com/uapi/v1.0/consents', $param);
        $data = json_decode($response);

        if (isset($data->Data->consentId)) {
            $this->consentId = $data->Data->consentId;
        } else {
            Log::error('Не удалось получить consentId для client_id: '.$this->client_id);
            Log::error(json_encode($data));
            $this->consentId = false;
        }
    }

    /**
     *  Возвращает ссылку для подтверждения разрешений в банке
     *
     * @return string Url
     */
    private function getConfirmConsentUrl()
    {
        $str = 'https://enter.tochka.com/connect/authorize?client_id='.$this->client_id.'&response_type=code'.
            '&state=APP&redirect_uri='.$this->redirect_uri.'&scope='.$this->scope.'&consent_id='.$this->consentId;

        return $str;
    }

    /**
     *  Запрос на выдачу
     *
     * @param string $code Код полученный после подтверждения разрешений в банке
     * @param string $redirect_uri Url прописываемый в банке при регистрации
     * @return array возвращает client_id, access_token, refresh_token
     */
    public function getTokens($code, $redirect_uri)
    {
        $param = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $this->grant_type_au,
            'scope' => $this->scope,
            'code' => $code,
            'redirect_uri' => $redirect_uri
        ];
        $response = Http::asForm()->post('https://enter.tochka.com/connect/token', $param);
        $data = json_decode($response);

        if (isset($data->refresh_token)) {
            $this->access_token = $data->access_token;
            $this->refresh_token = $data->refresh_token;
        } else {
            Log::error('Не удалось получить refresh_token для client_id: '.$this->client_id);
            Log::error(json_encode($data));
            $this->access_token = false;
            $this->refresh_token = false;
        }

        return ['client_id' => $this->client_id, 'access_token' => $this->access_token, 'refresh_token' => $this->refresh_token];
    }

    /**
     * @param string $refresh_token refresh_token используемый для получения нового access_token
     * @return array возвращает client_id, access_token, refresh_token
     */
    public function refreshTokens($refresh_token)
    { //
        $param = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $this->grant_type_re,
            'refresh_token' => $refresh_token
        ];
        $response = Http::asForm()->post('https://enter.tochka.com/connect/token', $param);
        $data = json_decode($response);

        if (isset($data->refresh_token)) {
            $this->access_token = $data->access_token;
            $this->refresh_token = $data->refresh_token;
        } else {
            Log::error('Не удалось получить refresh_token для client_id: '.$this->client_id);
            Log::error(json_encode($data));
            $this->access_token = false;
            $this->refresh_token = false;
        }

        return ['client_id' => $this->client_id, 'access_token' => $this->access_token, 'refresh_token' => $this->refresh_token];
    }
}
