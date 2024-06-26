<?php

declare(strict_types=1);

namespace App\TradingStrategy;

use App\Entity\Coin;
use Doctrine\ORM\EntityManagerInterface;

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
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->strategies = ['always-buy' => new AlwaysBuyStrategy($this->entityManager)];
    }

    public function create(string $name): TradingStrategyInterface
    {
        return $this->strategies[$name];
    }
}
