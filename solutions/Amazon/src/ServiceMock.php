<?php
declare(strict_types=1);

namespace App;

use App\Data\AbstractOrder;
use App\Data\BuyerInterface;

final class ServiceMock implements ShippingServiceInterface
{
    private ?string $logFile = __DIR__ . '/mock_shipments.log';

    public function __construct(?string $logFile = __DIR__ . '/mock_shipments.log')
    {
        $this->logFile = $logFile;
    }

    public function ship(AbstractOrder $order, BuyerInterface $buyer): string
    {
        $orderId   = $order->getOrderId();
        $buyerName = $buyer['name']
            ?? $buyer['shop_username']
            ?? 'Buyer';

        $tracking = $this->generateTracking('NA');
        $this->log($orderId, (string)$buyerName, $tracking);

        return $tracking;
    }

    private function generateTracking(string $region): string
    {
        $ts   = (new \DateTimeImmutable())->format('YmdHis');
        $rand = strtoupper(bin2hex(random_bytes(6)));
        $r    = preg_replace('~[^A-Z0-9]~', '', strtoupper($region)) ?: 'NA';
        return sprintf('FBA-%s-%s-%s', $r, $ts, $rand);
    }

    private function log(int|string $orderId, string $buyerName, string $tracking): void
    {
        if (!$this->logFile) return;
        $line = sprintf("[%s] MOCK FBA | order=%s | buyer=%s | tracking=%s\n",
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $orderId,
            $buyerName,
            $tracking
        );
        @file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}
