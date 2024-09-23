<?php

declare(strict_types=1);

namespace App\Workflow\Order\EventSubscriber;

use App\Entity\Order;
use ByBit\SDK\ByBitApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

/**
 * Обработчик событий заказа
 */
class OrderEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ByBitApi $byBitApi)
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [TransitionEvent::getName('order', 'cancel') => 'startCancelling'];
    }


    public function startCancelling(TransitionEvent $event): void
    {
        $order = $event->getSubject();
        assert($order instanceof Order);
        $this->byBitApi->tradeApi()->cancelOrder(
            [
                'orderLinkId' => $order->getId(),
                'category' => $order->getCategory()->value,
                'symbol' => $order->getSymbol(),
            ]
        );
    }
}
