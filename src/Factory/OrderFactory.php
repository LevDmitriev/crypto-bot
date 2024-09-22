<?php

namespace App\Factory;

use App\Entity\Coin;
use App\Entity\Order;
use App\Entity\Order\ByBit\Category;
use App\Entity\Order\ByBit\OrderFilter;
use App\Entity\Order\ByBit\Side;
use App\Entity\Order\ByBit\Type;

/**
 * Фабрика приказов
 */
class OrderFactory
{
    public function create(
        Coin $coin,
        ?string $quantity = null,
        ?string $price = null,
        ?string $triggerPrice = null,
        Side $side = Side::Buy,
        Category $category = Category::spot,
        Type $type = Type::Market,
        OrderFilter $orderFilter = OrderFilter::Order
    ): Order {
        return (new Order())
            ->setCoin($coin)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setSide($side)
            ->setCategory($category)
            ->setType($type)
            ->setOrderFilter($orderFilter)
            ->setTriggerPrice($triggerPrice)
        ;
    }
}
