<?php
declare(strict_types=1);

namespace App\Data;

final class MockOrder extends AbstractOrder
{
    /**
     * В этом мок-классе просто возвращаем данные из JSON.
     * Метод будет вызван базовым final::load().
     */
    protected function loadOrderData(int $id): array
    {
        // Для мока — читаем заранее загруженный массив, проброшенный через фабрику (см. test.php).
        // Чтобы не держать глобальное состояние, можно хранить кэш в статике по ID.
        return self::$cache[$id] ?? [];
    }

    /** Простейший статический кэш данных заказов по ID (для мока) */
    private static array $cache = [];

    /**
     * Фабрика мока: создаёт объект и «подкладывает» данные в кэш по его ID.
     */
    public static function fromArray(array $order): self
    {
        // Определяем идентификатор так, как требует базовый конструктор (int)
        $id = (int)($order['order_id'] ?? $order['id'] ?? 0);
        $id = $id > 0 ? $id : random_int(10000, 99999);

        $self = new self($id); // ВАЖНО: вызовет parent::__construct(int $id)
        self::$cache[$id] = $order;

        // Подгрузим данные сразу (заполнит $this->data в базовом классе)
        $self->load();

        return $self;
    }
}
