<?php

namespace App\MessageHandler;

use App\Factory\OrderFactory;
use App\Messages\CreateOrderToPositionCommand;
use App\Repository\CoinRepository;
use App\Repository\OrderRepository;
use App\Repository\PositionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Обработчик комманды на создание приказа для позиции
 */
readonly class CreateOrderToPositionCommandHandler
{
    public function __construct(
        private PositionRepository $positionRepository,
        private EntityManagerInterface $entityManager,
        private CoinRepository $coinRepository,
        private OrderFactory $orderFactory,
    ) {
    }

    public function __invoke(CreateOrderToPositionCommand $command)
    {
        $position = $this->positionRepository->find($command->positionId);
        $coin = $this->coinRepository->find($command->coinId);
        $order = $this->orderFactory->create(
            coin:         $coin,
            quantity:     $command->quantity,
            price:        $command->price,
            triggerPrice: $command->triggerPrice,
            side:         $command->side,
            category:     $command->category,
            type:         $command->type,
            orderFilter:  $command->orderFilter
        );
        $position->addOrder($order);
        $this->entityManager->persist($position);
    }
}
