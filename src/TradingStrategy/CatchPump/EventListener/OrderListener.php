<?php

namespace App\TradingStrategy\CatchPump\EventListener;

use App\Entity\Order;
use App\TradingStrategy\CatchPump\Strategy\CatchPumpStrategy;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsEntityListener(event: Events::postUpdate, method: 'closePosition', entity: Order::class)]
class OrderListener
{
    public function __construct(
        private WorkflowInterface $positionStateMachine,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Закрыть позицию, если выполнен стоп-приказ
     * @param Order $order
     *
     * @return void
     */
    public function closePosition(Order $order): void
    {
        if ($order->getPosition()?->getStrategyName() === CatchPumpStrategy::NAME) {
            if ($order->isStop() && $order->isFilled() && $this->positionStateMachine->can($order->getPosition(), 'close')) {
                $this->positionStateMachine->apply($order->getPosition(), 'close');
                $this->entityManager->persist($order->getPosition());
                $this->entityManager->flush();
            }
        }
    }
}
