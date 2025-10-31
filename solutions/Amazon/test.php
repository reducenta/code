<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\ServiceMock;
use App\Data\MockOrder;
use App\Data\MockBuyer;
use App\ServiceAPI;

// пути к json
$orderPath = __DIR__ . '/mock/order.json';
$buyerPath = __DIR__ . '/mock/buyer.json';

// читаем json
$orderData = json_decode(file_get_contents($orderPath), true, 512, JSON_THROW_ON_ERROR);
$buyerData = json_decode(file_get_contents($buyerPath), true, 512, JSON_THROW_ON_ERROR);

// создаём объекты
$order = MockOrder::fromArray($orderData);
$buyer = new MockBuyer($buyerData);

echo "Order ID: {$order->getOrderId()}\n";
echo "Buyer:    " . ($buyer['name'] ?? $buyer['shop_username'] ?? 'Buyer') . "\n";

// ====================== ServiceMock ======================
echo "\n=== ServiceMock ===\n";
$mock = new ServiceMock(__DIR__ . '/mock_shipments.log');
$trackingMock = $mock->ship($order, $buyer);
echo "Tracking (MOCK): {$trackingMock}\n";

// ====================== ServiceAPI ======================
echo "\n=== ServiceAPI ===\n";

$lwaClientId        = 'FAKE_LWA_CLIENT_ID';
$lwaClientSecret    = 'FAKE_LWA_CLIENT_SECRET';
$lwaRefreshToken    = 'FAKE_LWA_REFRESH_TOKEN';
$awsAccessKeyId     = 'FAKE_AWS_ACCESS_KEY';
$awsSecretAccessKey = 'FAKE_AWS_SECRET_KEY';
$roleArn            = 'arn:aws:iam::123456789012:role/FakeSpApiRole';
$region             = 'eu-west-1';   // пример: us-east-1, eu-west-1
$endpoint           = 'EU';          // EU | NA | FE

try {
    $api = new ServiceAPI(
        $lwaClientId,
        $lwaClientSecret,
        $lwaRefreshToken,
        $awsAccessKeyId,
        $awsSecretAccessKey,
        $roleArn,
        $region,
        $endpoint
    );

    $trackingApi = $api->ship($order, $buyer);
    echo "Tracking (API): {$trackingApi}\n";
} catch (Throwable $e) {
    echo "[ERROR][API] " . $e->getMessage() . "\n";
}

