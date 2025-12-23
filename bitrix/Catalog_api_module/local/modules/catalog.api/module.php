<?php

use Bitrix\Main\ModuleManager;

class catalog_api extends CModule
{
    public $MODULE_ID = "catalog.api";
    public $MODULE_VERSION = "1.0.0";
    public $MODULE_VERSION_DATE = "2025-12-23 00:00:00";
    public $MODULE_NAME = "Catalog API";
    public $MODULE_DESCRIPTION = "REST API для каталога";

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}