<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Position;
use App\Messages\PositionCreatedMessage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * После создания позиции отправить сообщение об этом
 */
#[AsEntityListener(event: Events::postPersist, method: 'sendMessage', entity: Position::class)]
class PostPersistPositionSendMessageListener
{
    public function __construct(private readonly MessageBusInterface $messageBus)
    {
    }

    /**
     * Отправить сообщение с информацией по позиции
     *
     * @param Position $position
     *
     * @return void
     * @throws \Symfony\Component\Messenger\Exception\ExceptionInterface
     */
    public function sendMessage(Position $position): void
    {
        $id = $position->getId();
        if ($id) {
            $message = new PositionCreatedMessage($id);
            $this->messageBus->dispatch($message);
        }
    }
}
