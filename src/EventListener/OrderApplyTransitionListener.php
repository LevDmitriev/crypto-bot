<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Order;
use App\Entity\Order\ByBit\Status;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Обработчик, который двигает Приказ по статусам в зависимости от статуса ByBit
 */
#[AsEntityListener(event: Events::preUpdate, method: 'applyTransition', entity: Order::class)]
class OrderApplyTransitionListener
{
    public function __construct(private readonly WorkflowInterface $orderStateMachine)
    {
    }

    public function applyTransition(Order $order): void
    {
        match ($order->getByBitStatus()) {
            Status::PartiallyFilledCanceled, Status::Filled => $this->orderStateMachine->apply($order, 'fill'),
            Status::Cancelled, Status::Deactivated => $this->orderStateMachine->apply($order, 'cancel')
        };
    }
}
