<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Order;
use App\Entity\Position;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;

/**
 * После того как приказ полностью выполнен, необходимо создать позицию
 */
#[AsEntityListener(event: Events::postUpdate, method: 'createPosition', entity: Order::class)]
class CreatePositionEventListener
{
    public function __construct(private  readonly EntityManagerInterface $entityManager)
    {
    }

    public function createPosition(Order $order): void
    {
        if ($order->getStatus() === Order\ByBit\Status::Filled->value && $order->getSide() === Order\ByBit\Side::Buy) {
            $position = new Position();
            $position->setBuyOrder($order);
            $this->entityManager->persist($position);
            $this->entityManager->flush();
        }
    }
}
