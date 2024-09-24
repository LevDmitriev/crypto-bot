<?php

namespace App\Messages;

use App\Entity\Coin;
use App\Entity\Order\ByBit\Category;
use App\Entity\Order\ByBit\OrderFilter;
use App\Entity\Order\ByBit\Side;
use App\Entity\Order\ByBit\Type;

/**
 * Команда создания приказа к позиции
 */
readonly class CreateOrderToPositionCommand
{
    public function __construct(
        public string $positionId,
        public int $coinId,
        public ?string $quantity = null,
        public ?string $price = null,
        public ?string $triggerPrice = null,
        public Side $side = Side::Buy,
        public Category $category = Category::spot,
        public Type $type = Type::Market,
        public OrderFilter $orderFilter = OrderFilter::Order
    ) {
    }
}
