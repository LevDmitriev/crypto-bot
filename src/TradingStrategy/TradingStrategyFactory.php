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
class TradingStrategyFactory implements TradingStrategyFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    public function __construct(
        private readonly EntityManagerInterface    $entityManager,
        private readonly CandleRepositoryInterface $candleRepository,
        private readonly PositionRepository $positionRepository,
        private readonly AccountRepository         $accountRepository,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly OrderFactory $orderFactory,
        private readonly MessageBusInterface $commandBus,
        private readonly WorkflowInterface $positionStateMachine,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function create(string $name, Coin $coin): TradingStrategyInterface
    {
        $strategy =
         match ($name) {
            CatchPumpStrategy::NAME => new CatchPumpStrategy(
                coin:                 $coin,
                entityManager:        $this->entityManager,
                candleRepository:     $this->candleRepository,
                positionRepository:   $this->positionRepository,
                accountRepository:    $this->accountRepository,
                dispatcher:           $this->dispatcher,
                orderFactory:         $this->orderFactory,
                commandBus:           $this->commandBus,
                positionStateMachine: $this->positionStateMachine,
                lockFactory:          $this->lockFactory
            )
        };
        if ($strategy instanceof LoggerAwareInterface) {
            $strategy->setLogger($this->logger);
        }
        return $strategy;
    }
}
