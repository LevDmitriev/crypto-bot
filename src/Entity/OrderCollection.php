<?php

namespace App\Entity;

use App\Entity\Order\ByBit\Side;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Коллекция приказов
 * @extends ArrayCollection<int, Order>
 */
class OrderCollection extends ArrayCollection
{
    /**
     * Получить приказы на продажу
     * @return self
     */
    public function filterSellOrders(): self
    {
        return $this->filter(fn (Order $order) => $order->getSide() === Side::Sell);
    }
    /**
     * Получить приказы на покупку
     * @return self
     */
    public function filterBuyOrders(): self
    {
        return $this->filter(fn (Order $order) => $order->getSide() === Side::Buy);
    }
    /**
     * Получить стоп-приказы
     * @return self
     */
    public function filterStopOrders(): self
    {
        return $this->filter(fn (Order $order) => $order->isStop());
    }

    /**
     * Получить рыночные приказы
     * @return self
     */
    public function filterMarketOrders(): self
    {
        return $this->filter(fn (Order $order) => $order->isMarket());
    }

    /**
     * Отфильтровать по количеству
     * @return self
     */
    public function filterByQuantity(string $quantity): self
    {
        return $this->filter(fn (Order $order) => $order->getQuantity() === $quantity());
    }

    /**
     * Получить обычные приказы
     * @return self
     */
    public function filterCommonOrders(): self
    {
        return $this->filter(fn (Order $order) => $order->isCommon());
    }
}
