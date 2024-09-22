<?php

namespace App\TradingStrategy;

use App\Entity\Position;

/**
 * Интерфейс торговой стратегии
 */
interface TradingStrategyInterface
{
    /**
     * Проанализировать данные и отстрелить все необходимые события.
     * @return void
     */
    public function dispatchEvents(): void;
}
