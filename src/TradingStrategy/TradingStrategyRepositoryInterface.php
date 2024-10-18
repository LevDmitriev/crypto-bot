<?php

namespace App\TradingStrategy;

use App\Entity\Coin;

/**
 * Фабрика торговых стратегий
 */
interface TradingStrategyRepositoryInterface
{
    public function get(string $name): TradingStrategyInterface;
}
