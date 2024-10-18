<?php

declare(strict_types=1);

namespace App\TradingStrategy;

use App\Entity\Coin;
use App\Factory\OrderFactory;
use App\Market\Repository\CandleRepositoryInterface;
use App\Repository\AccountRepository;
use App\Repository\PositionRepository;
use App\TradingStrategy\CatchPump\Strategy\CatchPumpStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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
