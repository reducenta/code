<?php

/**
 * Класс для расчета доставки "Деловые линии"
 * "Delovie linii" api delivery calculate class
 */

namespace App\Components\Delivery;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DelLin
{
    const API_KEY = 'key';
    const LOGIN = 'login';
    const PASSWORD = 'password';

    const AUTH_URL = 'https://api.dellin.ru/v3/auth/login.json';
    const CALCULATE_URL = 'https://api.dellin.ru/v2/calculator.json';

    const DERIVAL_ADDRESS = 'address';

    public $client;
    public $session_id;


    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function __construct(){
        $this->client = new Client([
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ]);

        $this->auth();
    }

    public function responseParse($response): array
    {
        $data = json_decode($response->getBody()->getContents(), true);

        $res = [
            'status' => false,
            'errors' => [],
            'data' => []
        ];

        if(!empty($data['errors'])){
            $res['status'] = false;
            $res['errors'] = $data['errors'];
        }else{
            $res['status'] = true;
            $res['data'] = $data['data'];
        }

        return $res;
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function auth(): bool
    {
        $body = [
            'body' => json_encode([
                'appkey' => self::API_KEY,
                'login' => self::LOGIN,
                'password' => self::PASSWORD
            ])
        ];

        $auth = $this->responseParse($this->client->post(self::AUTH_URL, $body));

        if(!$auth['status']){
            $message['mess'] = 'Ошибка подключения';
            $message['errors'] = [];
            foreach($auth['errors'] as $error){

                $message['errors'][$error['code']] = $error['detail'];
            }

            throw new Exception(json_encode($message));
        }else{
            $this->session_id = $auth['data']['sessionID'];
            return true;
        }

    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function calculate($options) : int
    {
        $body = [
            'body' => json_encode([
                'appkey' => self::API_KEY,
                'sessionID' => $this->session_id,
                'delivery' => [
                    'deliveryType' => [
                        'type' => 'auto'
                    ],
                    'derival' => [
                        'variant' => 'address',
                        "produceDate" => date('Y-m-d', time() + 86400),
                        'address' => [
                            'search' => self::DERIVAL_ADDRESS,
                        ],
                        'time' => [
                            "worktimeStart" => "9:00",
                            "worktimeEnd" => "19:00"
                        ]
                    ],
                    'arrival' => [
                        'variant' => 'address',
                        'address' => [
                            'search' => $options['address'],
                        ],
                        'time' => [
                            "worktimeStart" => "9:00",
                            "worktimeEnd" => "19:00"
                        ]
                    ],
                ],
                'cargo' => [
                    "length" => 1,
                    "width" => 1,
                    "height" => 1,
                    "totalVolume" => $options['volume'],
                    "totalWeight" => $options['weight'],
                ]
            ])
        ];

        $calculate = $this->responseParse($this->client->post(self::CALCULATE_URL, $body));

        if(!$calculate['status']){
            $message['mess'] = 'Ошибка расчета';
            $message['errors'] = [];
            foreach($calculate['errors'] as $error){

                $message['errors'][$error['code']] = $error['detail'];
            }

            throw new Exception(json_encode($message));
        }else{
            return $calculate['data']['price'];
        }

    }

}
