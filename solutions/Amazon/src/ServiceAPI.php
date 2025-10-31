<?php
declare(strict_types=1);

namespace App;

use App\Data\AbstractOrder;
use App\Data\BuyerInterface;
use SellingPartnerApi\Api\FbaOutboundV20200701Api;
use SellingPartnerApi\Configuration;
use SellingPartnerApi\Endpoint;

/**
 * Реализация сервиса отгрузки через Amazon FBA (SP-API).
 * Полностью соответствует интерфейсу: ship(AbstractOrder $order, BuyerInterface $buyer): string
 */
final class ServiceAPI implements ShippingServiceInterface
{
    private FbaOutboundV20200701Api $api;

    /**
     * @param string $lwaClientId
     * @param string $lwaClientSecret
     * @param string $lwaRefreshToken
     * @param string $awsAccessKeyId
     * @param string $awsSecretAccessKey
     * @param string $roleArn                ARN роли IAM для SP-API
     * @param string $region                 Пример: 'eu-west-1', 'us-east-1'
     * @param 'NA'|'EU'|'FE' $endpointRegion Регионы SP-API (см. SellingPartnerApi\Endpoint)
     */
    public function __construct(
        string $lwaClientId,
        string $lwaClientSecret,
        string $lwaRefreshToken,
        string $awsAccessKeyId,
        string $awsSecretAccessKey,
        string $roleArn,
        string $region = 'eu-west-1',
        string $endpointRegion = 'EU'
    ) {
        $config = new Configuration([
            'lwaClientId'         => $lwaClientId,
            'lwaClientSecret'     => $lwaClientSecret,
            'lwaRefreshToken'     => $lwaRefreshToken,
            'awsAccessKeyId'      => $awsAccessKeyId,
            'awsSecretAccessKey'  => $awsSecretAccessKey,
            'roleArn'             => $roleArn,
            'region'              => $region,
            'endpoint'            => match (strtoupper($endpointRegion)) {
                'NA' => Endpoint::NA,
                'FE' => Endpoint::FE,
                default => Endpoint::EU,
            },
        ]);

        $this->api = new FbaOutboundV20200701Api($config);
    }

    /**
     * Создаёт FBA-отгрузку и возвращает tracking number.
     * Сигнатура строго совпадает с интерфейсом.
     */
    public function ship(AbstractOrder $order, BuyerInterface $buyer): string
    {
        // 1) Собираем входные данные (ID, адрес, позиции)
        $sellerFulfillmentOrderId = (string)$order->getOrderId();

        $address = $this->buildAddress($order, $buyer);
        $items   = $this->buildLineItems($order);

        if ($items === []) {
            throw new \RuntimeException('Не найдены товары в заказе: products[] пустой.');
        }

        // 2) Формируем тело запроса на создание FBA Fulfillment Order
        // Модель тела - CreateFulfillmentOrderRequest (SDK сам сериализует массив)
        $createRequest = [
            'sellerFulfillmentOrderId' => $sellerFulfillmentOrderId,
            'displayableOrderId'       => $sellerFulfillmentOrderId,
            'displayableOrderDate'     => (new \DateTimeImmutable())->format(DATE_ATOM),
            'displayableOrderComment'  => 'Order created via SP-API (auto)',
            'shippingSpeedCategory'    => 'Standard', // Standard | Expedited | Priority | ScheduledDelivery
            'destinationAddress'       => $address,
            'items'                    => $items,
            // опционально:
            // 'fulfillmentAction'     => 'Ship',
            // 'featureConstraints'    => [...],
            // 'notificationEmails'    => [$buyer['email'] ?? null],
        ];

        // 3) Создаём заказ в FBA
        $this->api->createFulfillmentOrder($createRequest);

        // 4) Запрашиваем сведения об отгрузке и извлекаем tracking number
        //    В ответе будет набор shipments/packages; берём первый доступный трек
        $fo = $this->api->getFulfillmentOrder($sellerFulfillmentOrderId);

        $tracking = $this->extractTrackingNumber($fo);
        if (!$tracking) {
            // Иногда трек появляется не сразу; в реальном проекте можно сделать небольшой повторный опрос.
            throw new \RuntimeException("Создан FBA-ордер {$sellerFulfillmentOrderId}, но trackingNumber пока не доступен.");
        }

        return $tracking;
    }

    // ----------------------- helpers -----------------------

    /**
     * Адрес доставки в формате SP-API.
     * Пытаемся взять из $order->data + дополняем из $buyer при необходимости.
     *
     * @return array{
     *   name:string,addressLine1:string,city:string,stateOrProvinceCode?:string,
     *   postalCode:string,countryCode:string,phone?:string
     * }
     */
    private function buildAddress(AbstractOrder $order, BuyerInterface $buyer): array
    {
        /** @var array<string,mixed> $data */
        $data = $order->data ?? [];

        $name   = (string)($data['buyer_name'] ?? $buyer['name'] ?? $buyer['shop_username'] ?? 'Buyer');
        $line1  = (string)($data['shipping_street'] ?? $data['shipping_address_line1'] ?? '');
        $city   = (string)($data['shipping_city'] ?? '');
        $state  = (string)($data['shipping_state'] ?? '');
        $zip    = (string)($data['shipping_zip'] ?? '');
        $cc     = (string)($data['shipping_country'] ?? $buyer['country_code'] ?? 'US');
        $phone  = (string)($buyer['phone'] ?? $data['phone'] ?? '');

        if ($line1 === '' || $city === '' || $zip === '' || $cc === '') {
            throw new \RuntimeException('Неполный адрес доставки (нужны line1, city, zip, country).');
        }

        $addr = [
            'name'                 => $name,
            'addressLine1'         => $line1,
            'city'                 => $city,
            'postalCode'           => $zip,
            'countryCode'          => $cc,
        ];
        if ($state !== '') {
            $addr['stateOrProvinceCode'] = $state;
        }
        if ($phone !== '') {
            $addr['phone'] = $phone;
        }
        return $addr;
    }

    /**
     * Преобразует позиции заказа к формату SP-API items[].
     * Требуются поля: sellerSku, sellerFulfillmentOrderItemId, quantity
     *
     * @return list<array{sellerSku:string,sellerFulfillmentOrderItemId:string,quantity:int}>
     */
    private function buildLineItems(AbstractOrder $order): array
    {
        /** @var array<string,mixed> $data */
        $data = $order->data ?? [];
        $src  = isset($data['products']) && is_array($data['products']) ? $data['products'] : [];

        $items = [];
        $idx   = 1;

        foreach ($src as $p) {
            $sku = (string)($p['sku'] ?? $p['product_code'] ?? '');
            $qty = (int)($p['ammount'] ?? $p['qty'] ?? 0);

            if ($sku === '' || $qty <= 0) {
                // пропускаем битые позиции
                continue;
            }

            $items[] = [
                'sellerSku' => $sku,
                'sellerFulfillmentOrderItemId' => (string)($p['order_product_id'] ?? ($order->getOrderId() . '-' . $idx)),
                'quantity' => $qty,
            ];
            $idx++;
        }

        return $items;
    }

    /**
     * Вынимает первый доступный trackingNumber из ответа getFulfillmentOrder()
     */
    private function extractTrackingNumber(array|\ArrayAccess $fo): ?string
    {
        // Ответ SDK — ассоциативный массив/массив моделей; пойдём осторожно.
        // Ищем путь: fulfillmentShipments[] -> fulfillmentShipmentPackage[] -> trackingNumber
        $arr = is_array($fo) ? $fo : (array)$fo;

        $shipments = $arr['payload']['fulfillmentShipments'] ?? $arr['fulfillmentShipments'] ?? null;
        if (!is_array($shipments)) {
            return null;
        }

        foreach ($shipments as $s) {
            $packages = $s['fulfillmentShipmentPackage'] ?? $s['packages'] ?? null;
            if (!is_array($packages)) {
                continue;
            }
            foreach ($packages as $pkg) {
                $tn = $pkg['trackingNumber'] ?? $pkg['packageTrackingDetails']['trackingId'] ?? null;
                if (is_string($tn) && $tn !== '') {
                    return $tn;
                }
            }
        }

        return null;
        // Примечание: структура может отличаться в зависимости от версии SDK/ответа.
        // При желании можно var_export($fo) в лог и подстроить парсинг.
    }
}
