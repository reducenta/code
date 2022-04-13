<?php

/**
 * Php wrapper for Highloadblock
 */

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

class Highload
{
    public $hlblock_id;

    public function __construct($hlblock_id)
    {
        Loader::includeModule("highloadblock");
        $this->hlblock_id = $hlblock_id;
    }

    private function _error_format($message, $trace){
        $message = 'Ошибка ' . $message . PHP_EOL;
        foreach($trace as $item){
            $message .= $item['file'] . ':' . $item['line'] . PHP_EOL;
        }

        return $message;
    }

    public function get_entity(){
        $hlblock = HL\HighloadBlockTable::getById($this->hlblock_id)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        return $entity->getDataClass();
    }

    public function add($data){
        $entity = $this->get_entity();
        try{
            $rs = $entity::add($data);
            return $rs->getId();
        }catch (Exception $e){
            return $this->_error_format($e->getMessage(), $e->getTrace());
        }
    }

    public function getList($data){
        $entity = $this->get_entity();
        $return = [];
        $list = $entity::getList($data);
        try {
            while($item = $list->fetch()){
                $return[] = $item;
            }
            return $return;
        }catch (Exception $e){
            return $this->_error_format($e->getMessage(), $e->getTrace());
        }

    }

    public function getByID($id){
        $entity = $this->get_entity();
        $list = $entity::getList(['filter' => ['ID' => $id]]);
        try {
            return $list->fetch();
        }catch (Exception $e){
            return $this->_error_format($e->getMessage(), $e->getTrace());
        }

    }

    public function getByCode($code){
        $entity = $this->get_entity();
        $list = $entity::getList(['filter' => ['UF_XML_ID' => $code]]);
        try {
            return $list->fetch();
        }catch (Exception $e){
            return $this->_error_format($e->getMessage(), $e->getTrace());
        }

    }

    public function update($id, $data){
        $entity = $this->get_entity();
        try {
            $rs = $entity::update($id, $data);
            return $rs->getId();
        }catch (Exception $e){
            return $this->_error_format($e->getMessage(), $e->getTrace());
        }
    }

    public function delete($id){
        $entity = $this->get_entity();
        return $entity::delete($id);
    }

}