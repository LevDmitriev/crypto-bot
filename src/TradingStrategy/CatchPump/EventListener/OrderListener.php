<?php

namespace App\TradingStrategy\CatchPump\EventListener;

use App\Entity\Order;
use App\TradingStrategy\CatchPump\Strategy\CatchPumpStrategy;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsEntityListener(event: Events::postUpdate, method: 'closePosition', entity: Order::class)]
class OrderListener
{
    public function __construct(
        private WorkflowInterface $positionStateMachine
    ) {
    }

    /**
     * Закрыть позицию, если выполнен стоп-приказ
     * @param Order $order
     *
     * @return void
     */
    public function closePosition(Order $order)
    {
        if ($order->getPosition()?->getStrategyName() === CatchPumpStrategy::NAME) {
            if ($order->isStop() && $order->isFilled()) {
                $this->positionStateMachine->apply($order->getPosition(), 'close');
            }
        }
    }
}
