<?php

declare(strict_types=1);

namespace App\Workflow\Position;

use App\Entity\Order\ByBit\Side;
use App\Entity\Position;
use App\Messages\CreateOrderToPositionCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Обработчик событий позиции
 */
class PositionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly WorkflowInterface $orderStateMachine,
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            TransitionEvent::getName('position', 'close') => 'cancelAllOrders',
            TransitionEvent::getName('position', 'close') => 'sellAllCoins',
        ];
    }


    public function cancelAllOrders(TransitionEvent $event): void
    {
        $position = $event->getSubject();
        assert($position instanceof Position);
        foreach ($position->getOrders() as $order) {
            if ($this->orderStateMachine->can($order, 'cancel')) {
                $this->orderStateMachine->apply($order, 'cancel');
            }
        }
    }

    /**
     * Добавить в позицию приказ на продажу оставшихся монет
     * @param TransitionEvent $event
     *
     * @return void
     */
    public function sellAllCoins(TransitionEvent $event): void
    {
        /** @var Position $position */
        $position = $event->getSubject();
        assert($position instanceof Position);
        $this->commandBus->dispatch(
            new CreateOrderToPositionCommand(
                positionId: $position->getId(),
                quantity:   $position->getNotSoldQuantity(),
                side:       Side::Sell
            ),
            [new TransportMessageIdStamp("create_order_to_position_{$position->getId()}")]
        );
    }
}
