<?php


namespace FEDmns\TochkaAPI;

use Illuminate\Support\Facades\Http;
use phpDocumentor\Reflection\Types\Collection;

/**
 * Class TochkaAPI
 * @package FEDmns\TochkaAPI
 */
class TochkaAPI
{

    /**
     * @var string
     */
    private $api_url = 'https://enter.tochka.com/uapi/open-banking/v1.0/';

    /**
     *  access_token для доступа к API
     *
     * @var string
     */
    private $access_token;

    /**
     * TochkaAPI constructor.
     * @param string $access_token access_token для доступа к API
     */
    public function __construct($access_token)
    {
        $this->access_token= $access_token;
    }

    /**
     *  Метод получения списка доступных счетов
     *
     * @return mixed
     */
    public function getAccounts()
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'accounts');

        return $response->object()->Data->Account;
    }

    /**
     *  Метод получения информации по конкретному счёту
     *
     * @param string $accountId
     * @return mixed
     */
    public function getAccount($accountId)
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'accounts/'.$accountId);

        return $response->object()->Data;
    }

    /**
     *  Метод получения баланса по нескольким счетам
     *
     * @return mixed
     */
    public function getBalances()
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'balances');

        return $response->object()->Data->Balance;
    }

    /**
     *  Метод получения информации о балансе конкретного счета
     *
     * @param string $accountId
     * @return mixed
     */
    public function getBalance($accountId)
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'accounts/'.$accountId.'/balances');

        return $response->object()->Data->Balance;
    }

    /**
     *  Метод создания выписки по конкретному счету
     *
     * @param string $accountId
     * @param string $startDateTime Начало переода
     * @param string $endDateTime Конец периода
     * @return mixed
     */
    public function createStatement($accountId, $startDateTime, $endDateTime)
    {
        $param =    ["Data" =>
                        ["Statement" =>
                            [
                                "accountId" => $accountId,
                                "startDateTime" => $startDateTime,
                                "endDateTime" => $endDateTime
                            ]
                        ]
                    ];

        $response = Http::withToken($this->access_token)->post($this->api_url.'statements', $param);

        return $response->object()->Data->Statement;
    }

    /**
     *  Метод получения списка доступных выписок
     *
     * @return mixed
     */
    public function getStatements()
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'statements');

        return $response->object()->Data->Statement;
    }

    /**
     *  Метод получения конкретной выписки
     *
     * @param string $accountId
     * @param string $statementId
     * @return mixed
     */
    public function getStatement($accountId, $statementId)
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'accounts/'.$accountId.'/statements/'.$statementId);

        return $response->object()->Data->Statement;
    }

}
