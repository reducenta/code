<?php
// Одноразовая ручная регистрация модуля catalog.api
// В случае, если модуль не устанавливается из админки
// запустить напрямую из браузера /local/install_catalog_api.php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\ModuleManager;

ModuleManager::registerModule('catalog.api');

echo 'Module catalog.api registered';