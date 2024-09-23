<?php

declare(strict_types=1);

namespace App\Workflow\Position;

use App\Entity\Order;
use App\Entity\Position;
use ByBit\SDK\ByBitApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Обработчик событий позиции
 */
class PositionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly WorkflowInterface $orderStateMachine)
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [TransitionEvent::getName('position', 'close') => 'cancelAllOrders'];
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
}
