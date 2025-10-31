<?php
declare(strict_types=1);

namespace App\Data;

use ArrayAccess;
use ArrayObject;

/**
 * Минимальная реализация BuyerInterface через ArrayObject,
 * чтобы удовлетворить ArrayAccess и «магические» свойства.
 */
final class MockBuyer extends ArrayObject implements BuyerInterface
{
    public function __construct(array $data = [])
    {
        parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
    }
}
