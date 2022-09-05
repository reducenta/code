<?php


class Bitrix
{
    public function __construct()
    {
        $_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../../");

        define("NO_KEEP_STATISTIC", true);
        define("NOT_CHECK_PERMISSIONS",true);
        define("BX_CRONTAB", true);
        define('BX_NO_ACCELERATOR_RESET', true);

        require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

        CModule::IncludeModule("iblock");
        CModule::IncludeModule("catalog");
    }
}