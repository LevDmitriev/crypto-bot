<?php

namespace App\TradingStrategy;

/**
 * Фабрика торговых стратегий
 */
interface TradingStrategyFactoryInterface
{
    public function create(string $name): TradingStrategyInterface;
}
