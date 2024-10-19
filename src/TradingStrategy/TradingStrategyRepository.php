<?php

declare(strict_types=1);

namespace App\TradingStrategy;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Фабрика торговых стратегий
 */
class TradingStrategyRepository implements TradingStrategyRepositoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    public function __construct(
        private array $strategies = []
    ) {
    }

    public function get(string $name): TradingStrategyInterface
    {
        return $this->strategies[$name];
    }

    public function add(string $name, TradingStrategyInterface $strategy): void
    {
        $this->strategies[$name] = $strategy;
    }
}
