<?php
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$this->arResult = [
    'STATUS' => '',
    'MESSAGE' => ''
];

if ($_SERVER['REQUEST_METHOD'] == "POST"){


    if(Loader::includeModule('andreev.dev.feedbackform')) {

        $hlblock = HL\HighloadBlockTable::getById(andreev_dev_feedbackform::getHlID())->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock)->getDataClass();

        try{
            $rs = $entity::add([
                'UF_FEEDBACKFORM_ADDED' => date('d.m.Y', time()),
                'UF_FEEDBACKFORM_USER_NAME' => $_POST['name'],
                'UF_FEEDBACKFORM_USER_EMAIL' => $_POST['email'],
                'UF_FEEDBACKFORM_USER_PHONE' => $_POST['phone'],
                'UF_FEEDBACKFORM_USER_MESSAGE' => $_POST['message'],
            ]);

            if(!empty($rs->getErrors())){
                $message = '';
                foreach($rs->getErrors() as $err){
                    $message .= $err->getMessage() . PHP_EOL;
                }

                $this->arResult['STATUS'] = 'fail';
                $this->arResult['MESSAGE'] = $message;
            }else{
                $this->arResult['STATUS'] = 'ok';
                $this->arResult['MESSAGE'] = 'Данные формы успешно сохранены';
            }

        }catch (Exception $e){
            $this->arResult['STATUS'] = 'fail';
            $this->arResult['MESSAGE'] = 'Ошибка сохранения формы';
        }


    }

}

$this->IncludeComponentTemplate();
?>