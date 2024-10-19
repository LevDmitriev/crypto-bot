<?php

namespace App\MessageHandler;

use App\Factory\OrderFactory;
use App\Messages\CreateOrderToPositionCommand;
use App\Repository\CoinRepository;
use App\Repository\OrderRepository;
use App\Repository\PositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Обработчик комманды на создание приказа для позиции
 */
#[AsMessageHandler]
readonly class CreateOrderToPositionCommandHandler
{
    public function __construct(
        private PositionRepository $positionRepository,
        private EntityManagerInterface $entityManager,
        private OrderFactory $orderFactory,
    ) {
    }

    public function __invoke(CreateOrderToPositionCommand $command)
    {
        $position = $this->positionRepository->find($command->positionId);
        if ($position) {
            $order = $this->orderFactory->create(
                coin:         $position->getCoin(),
                quantity:     $command->quantity,
                price:        $command->price,
                triggerPrice: $command->triggerPrice,
                side:         $command->side,
                category:     $command->category,
                type:         $command->type,
                orderFilter:  $command->orderFilter
            );
            $order->setPosition($position);
            $this->entityManager->persist($order);
        }
    }
}
