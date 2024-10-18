<?php

namespace App\MessageHandler;

use App\Messages\HandlePositionCommand;
use App\Repository\PositionRepository;
use App\TradingStrategy\TradingStrategyRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Обработчик открытой позиции
 */
#[AsMessageHandler]
readonly class HandleOpenedPositionHandler
{
    public function __construct(
        private TradingStrategyRepositoryInterface $tradingStrategyFactory,
        private PositionRepository $repository,
    ) {
    }

    public function __invoke(HandlePositionCommand $openedPositionCommand)
    {
        $position = $this->repository->find($openedPositionCommand->positionId);
        $strategy = $this->tradingStrategyFactory->get($position->getStrategyName());
        $strategy->handlePosition($position);
    }
}
