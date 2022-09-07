<?php
namespace App\Get;

class Pure implements Html
{
    /**
     * @param $url
     * @return false|string
     */

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
        return file_get_contents($this->origin);
    }
}