<?php

declare(strict_types=1);

namespace App\Workflow\Position;

use App\Entity\Order;
use App\Entity\Order\ByBit\Side;
use App\Entity\Position;
use App\Factory\OrderFactory;
use ByBit\SDK\ByBitApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Обработчик событий позиции
 */
class PositionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly WorkflowInterface $orderStateMachine,
        private readonly OrderFactory $orderFactory
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
        $position = $event->getSubject();
        assert($position instanceof Position);
        $sellOrder = $this->orderFactory->create(
            coin: $position->getCoin(),
            quantity: $position->getNotSoldQuantity(),
            side: Side::Sell
        );
        $position->addOrder($sellOrder);
    }
}
