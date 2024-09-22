<?php

namespace App\Factory;

use App\Entity\Coin;
use App\Entity\Order;
use App\Entity\Order\ByBit\Category;
use App\Entity\Order\ByBit\Side;

/**
 * Фабрика приказов
 */
class OrderFactory
{
    public function create(
        Coin $coin,
        ?string $quantity = null,
        ?string $price = null,
        Side $side = Side::Buy,
        Category $category = Category::spot,
        Order\ByBit\Type $type = Order\ByBit\Type::Market,
    ): Order {
        return (new Order())
            ->setCoin($coin)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setSide($side)
            ->setCategory($category)
            ->setType($type)
        ;
    }
}
