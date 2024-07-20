<?php

namespace App\TradingStrategy;

use App\Entity\Coin;

/**
 * Фабрика торговых стратегий
 */
interface TradingStrategyFactoryInterface
{
    public function create(string $name, Coin $coin): TradingStrategyInterface;
}
