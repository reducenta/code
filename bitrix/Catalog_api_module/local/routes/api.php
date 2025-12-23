<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Routing\RoutingConfigurator;
use Catalog\Api\Controller\ApiController;

// Подключаем модуль, чтобы заработала автозагрузка классов из Catalog\Api\
Loader::includeModule('catalog.api');

return function (RoutingConfigurator $routes) {

    // Подключаем модули
    $routes->get('/api/categories/{iblockId}', function ($iblockId) {

        $controller = new ApiController();
        $result = $controller->categoriesAction((int)$iblockId);

        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    });

    $routes->get('/api/products/{iblockId}/{categoryId}', function ($iblockId, $categoryId) {

        $controller = new ApiController();
        $result = $controller->productsAction((int)$iblockId, (int)$categoryId);

        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    });

    $routes->get('/api/product/{iblockId}/{productId}', function ($iblockId, $productId) {

        $controller = new ApiController();
        $result = $controller->productAction((int)$iblockId, (int)$productId);

        if ($result === null) {
            http_response_code(404);
            $result = ['error' => 'Товар не найден'];
        }

        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    });


};


