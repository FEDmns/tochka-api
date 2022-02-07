<?php


namespace FEDmns\TochkaAPI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class TochkaAPIAuth
{

    private string $client_id = '';
    private string $client_secret = '';
    private string $redirect_uri = '';

    private string $grant_type_cl = 'client_credentials';
    private string $grant_type_au = 'authorization_code';
    private string $grant_type_re = 'refresh_token';
    private string $scope = 'accounts cards customers sbp payments';
    private string $state = 'qwe';

    private bool|string $access_token = '';
    private bool|string $refresh_token = '';
    private bool|string $consentId = '' ;

    public function __construct($client_id, $client_secret, $redirect_uri) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_uri = $redirect_uri;
    }

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

    private function getConfirmConsentUrl()
    {
        $str = 'https://enter.tochka.com/connect/authorize?client_id='.$this->client_id.'&response_type=code'.
            '&state=APP&redirect_uri='.$this->redirect_uri.'&scope='.$this->scope.'&consent_id='.$this->consentId;

        return $str;
    }

    public function getTokens($code)
    {
        $param = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => $this->grant_type_au,
            'scope' => $this->scope,
            'code' => $code,
            'redirect_uri' => $this->redirect_uri
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

    public function refreshTokens($refresh_token)
    { // возвращает client_id, access_token, refresh_token
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
