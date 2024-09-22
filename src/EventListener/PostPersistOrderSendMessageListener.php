<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Order;
use App\Messages\SendOrderToByBitCommand;
use App\Messages\EnrichOrderFromByBitCommand;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEntityListener(event: Events::postPersist, method: 'sendMessage', entity: Order::class)]
class PostPersistOrderSendMessageListener
{
    public function __construct(private readonly MessageBusInterface $messageBus)
    {
    }

    /**
     * Отправить сообщение с информацией по приказу
     * @param Order $order
     *
     * @return void
     * @throws \Symfony\Component\Messenger\Exception\ExceptionInterface
     */
    public function sendMessage(Order $order): void
    {
        $id = $order->getId();
        if ($id) {
            $this->messageBus->dispatch(new SendOrderToByBitCommand($id));
        }
    }
}
