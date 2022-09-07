<?php

namespace App\Get;

class Curl implements Html
{
    protected $origin;

    /**
     * @throws \Exception
     */
    public function __construct($origin)
    {
        if(!filter_var($origin, FILTER_VALIDATE_URL)){
            throw new \Exception('В конструктор должна быть передана ссылка');
        }
        $this->origin = $origin;
    }

    public function get(){
        $ch = curl_init($this->origin);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }
}