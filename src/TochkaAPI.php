<?php


namespace FEDmns\TochkaAPI;

use Illuminate\Support\Facades\Http;
use phpDocumentor\Reflection\Types\Collection;

class TochkaAPI
{

    private $api_url = 'https://enter.tochka.com/uapi/open-banking/v1.0/';
    private $access_token;

    public function __construct($access_token)
    {
        $this->access_token= $access_token;
    }

    public function getAccounts()
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'accounts');

        return $response->collect();
    }

    public function getAccount($accountId)
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'accounts/'.$accountId);

        return $response->collect();
    }

    public function getBalances()
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'balances');

        return $response->collect();
    }

    public function getBalance($accountId)
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'accounts/'.$accountId.'/balances');

        return $response->collect();
    }

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

        return $response->collect();
    }

    public function getStatements()
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'statements');

        return $response->collect();
    }

    public function getStatement($accountId, $statementId)
    {
        $response = Http::withToken($this->access_token)->get($this->api_url.'accounts/'.$accountId.'/statements/'.$statementId);

        return $response->collect();
    }

}
