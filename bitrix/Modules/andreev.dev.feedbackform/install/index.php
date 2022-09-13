<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;

Loc::loadMessages(__FILE__);

class andreev_dev_feedbackform extends CModule
{
    var $MODULE_ID = "andreev.dev.feedbackform";
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;

    const HLBD_NAME = 'Feedbackform';
    const HLBD_TABLENAME = 'b_hlbd_feedbackform';

    public function __construct()
    {

        if (file_exists(__DIR__ . "/version.php")) {

            $arModuleVersion = array();

            include(__DIR__ . "/version.php");

            $this->MODULE_ID = str_replace("_", ".", get_class($this));
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
            $this->MODULE_NAME = Loc::getMessage("ANDREEV_DEV_NAME");
            $this->MODULE_DESCRIPTION = Loc::getMessage("ANDREEV_DEV_DESCRIPTION");
            $this->PARTNER_NAME = Loc::getMessage("ANDREEV_DEV_PARTNER_NAME");
            $this->PARTNER_URI = Loc::getMessage("ANDREEV_DEV_PARTNER_URI");
        }

        Loader::IncludeModule('highloadblock');

        return false;
    }

    public static function getHlID()
    {
        $res = HL\HighloadBlockTable::getList(['filter' => ['NAME' => self::HLBD_NAME], 'select' => ['ID']]);
        $hl = $res->fetch();
        return $hl['ID'];
    }

    public static function getHlFields($UFObject)
    {
        return [
            'UF_FEEDBACKFORM_ADDED'=>Array(
                'ENTITY_ID' => $UFObject,
                'FIELD_NAME' => 'UF_FEEDBACKFORM_ADDED',
                'USER_TYPE_ID' => 'string',
                'MANDATORY' => 'Y',
                "EDIT_FORM_LABEL" => Array('ru'=>'Дата добавления', 'en'=>'Date added'),
                "LIST_COLUMN_LABEL" => Array('ru'=>'Дата добавления', 'en'=>'Date added'),
                "LIST_FILTER_LABEL" => Array('ru'=>'Дата добавления', 'en'=>'Date added'),
                "ERROR_MESSAGE" => Array('ru'=>'', 'en'=>''),
                "HELP_MESSAGE" => Array('ru'=>'', 'en'=>''),
            ),

            'UF_FEEDBACKFORM_USER_NAME'=>Array(
                'ENTITY_ID' => $UFObject,
                'FIELD_NAME' => 'UF_FEEDBACKFORM_USER_NAME',
                'USER_TYPE_ID' => 'string',
                'MANDATORY' => 'Y',
                "EDIT_FORM_LABEL" => Array('ru'=>'Имя пользователя', 'en'=>'User name'),
                "LIST_COLUMN_LABEL" => Array('ru'=>'Имя пользователя', 'en'=>'User name'),
                "LIST_FILTER_LABEL" => Array('ru'=>'Имя пользователя', 'en'=>'User name'),
                "ERROR_MESSAGE" => Array('ru'=>'', 'en'=>''),
                "HELP_MESSAGE" => Array('ru'=>'', 'en'=>''),
            ),

            'UF_FEEDBACKFORM_USER_PHONE'=>Array(
                'ENTITY_ID' => $UFObject,
                'FIELD_NAME' => 'UF_FEEDBACKFORM_USER_PHONE',
                'USER_TYPE_ID' => 'string',
                'MANDATORY' => 'N',
                "EDIT_FORM_LABEL" => Array('ru'=>'Номер телефона', 'en'=>'Phone number'),
                "LIST_COLUMN_LABEL" => Array('ru'=>'Номер телефона', 'en'=>'Phone number'),
                "LIST_FILTER_LABEL" => Array('ru'=>'Номер телефона', 'en'=>'Phone number'),
                "ERROR_MESSAGE" => Array('ru'=>'', 'en'=>''),
                "HELP_MESSAGE" => Array('ru'=>'', 'en'=>''),
            ),

            'UF_FEEDBACKFORM_USER_EMAIL'=>Array(
                'ENTITY_ID' => $UFObject,
                'FIELD_NAME' => 'UF_FEEDBACKFORM_USER_EMAIL',
                'USER_TYPE_ID' => 'string',
                'MANDATORY' => 'Y',
                "EDIT_FORM_LABEL" => Array('ru'=>'EMail', 'en'=>'EMail'),
                "LIST_COLUMN_LABEL" => Array('ru'=>'EMail', 'en'=>'EMail'),
                "LIST_FILTER_LABEL" => Array('ru'=>'EMail', 'en'=>'EMail'),
                "ERROR_MESSAGE" => Array('ru'=>'', 'en'=>''),
                "HELP_MESSAGE" => Array('ru'=>'', 'en'=>''),
            ),

            'UF_FEEDBACKFORM_USER_MESSAGE'=>Array(
                'ENTITY_ID' => $UFObject,
                'FIELD_NAME' => 'UF_FEEDBACKFORM_USER_MESSAGE',
                'USER_TYPE_ID' => 'string',
                'MANDATORY' => 'Y',
                "EDIT_FORM_LABEL" => Array('ru'=>'Текст сообщения', 'en'=>'Message'),
                "LIST_COLUMN_LABEL" => Array('ru'=>'Текст сообщения', 'en'=>'Message'),
                "LIST_FILTER_LABEL" => Array('ru'=>'Текст сообщения', 'en'=>'Message'),
                "ERROR_MESSAGE" => Array('ru'=>'', 'en'=>''),
                "HELP_MESSAGE" => Array('ru'=>'', 'en'=>''),
            )
        ];
    }

    public function DoInstall()
    {
        global $APPLICATION;

        //Создаем хранилище для результатов
        //Хранить будем в Highload блоке

        $arLangs = Array(
            'ru' => 'Форма обратной связи',
            'en' => 'Feedback form'
        );

        $result = HL\HighloadBlockTable::add(array(
            'NAME' => self::HLBD_NAME,
            'TABLE_NAME' => self::HLBD_TABLENAME,
        ));

        if ($result->isSuccess()) {
            $UFObject = 'HLBLOCK_' . $result->getId();

            foreach($arLangs as $lang_key => $lang_val){
                HL\HighloadBlockLangTable::add(array(
                    'ID' => $result->getId(),
                    'LID' => $lang_key,
                    'NAME' => $lang_val
                ));
            }

            foreach(self::getHlFields($UFObject) as $field){
                $obUserField  = new CUserTypeEntity;
                $obUserField->Add($field);
            }

        }else{
            $errors = $result->getErrorMessages();
            if(!empty($errors)){
                $err_mess = '';
                foreach($errors as $err){
                    $err_mess .= $err . '<br>';
                }
                CAdminMessage::ShowNote($err_mess);
            }

            return false;
        }



        $this->InstallFiles();
        ModuleManager::registerModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("ANDREEV_DEV_INSTALL_TITLE") . " \"" . Loc::getMessage("ANDREEV_DEV_NAME") . "\"",
            __DIR__ . "/step.php"
        );

        return false;
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . "/components/andreev.dev/feedbackform",
            Application::getDocumentRoot() . "/bitrix/components/andreev.dev/feedbackform",
            true,
            true
        );

        return false;
    }

    public function InstallDB()
    {
        return false;
    }

    public function InstallEvents()
    {
        return false;
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $this->UnInstallFiles();
        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("ANDREEV_DEV_UNINSTALL_TITLE") . " \"" . Loc::getMessage("ANDREEV_DEV_NAME") . "\"",
            __DIR__ . "/unstep.php"
        );

        return false;
    }

    public function UnInstallFiles()
    {
        Directory::deleteDirectory(
            Application::getDocumentRoot() . "/bitrix/components/andreev.dev/feedbackform"
        );

        return false;
    }

    public function UnInstallDB()
    {
        try{
            $res = HL\HighloadBlockTable::delete($this->getHlID());
        }catch (Bitrix\Main\ArgumentException $e){
            return false;
        }

        return false;
    }

    public function UnInstallEvents()
    {
        return false;
    }

}