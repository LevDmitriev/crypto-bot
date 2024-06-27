<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Order;
use App\Entity\Position;
use Doctrine\ORM\EntityManagerInterface;

/**
 * После того как приказ полностью выполнен, необходимо создать позицию
 */
class CreatePositionEventListener
{
    public function __construct(private  readonly EntityManagerInterface $entityManager)
    {
    }

    public function createPosition(Order $order): void
    {
        if ($order->getStatus() === Order\Status::Filled && $order->getSide() === Order\Side::Buy) {
            $position = new Position();
            $position->setBuyOrder($order);
            $this->entityManager->persist($position);
            $this->entityManager->flush();
        }
    }
}
