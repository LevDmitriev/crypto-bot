<?php

declare(strict_types=1);

namespace App\TradingStrategy;

/**
 * Фабрика торговых стратегий
 */
class TradingStrategyFactory implements TradingStrategyFactoryInterface
{
    /**
     * Стратегии
     * @var TradingStrategyInterface[]
     */
    private array $strategies = [];
    public function __construct()
    {
        $this->strategies = ['always-buy' => new AlwaysBuyStrategy()];
    }

    public function create(string $name): TradingStrategyInterface
    {
        return $this->strategies[$name];
    }
}
