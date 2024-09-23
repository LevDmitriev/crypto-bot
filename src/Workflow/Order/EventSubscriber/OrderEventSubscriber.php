<?php

declare(strict_types=1);

namespace App\Workflow\Order\EventSubscriber;

use App\Bybit\ErrorCodes;
use App\Entity\Order;
use ByBit\SDK\ByBitApi;
use ByBit\SDK\Exceptions\HttpException;
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
        try {
            $this->byBitApi->tradeApi()->cancelOrder(
                [
                    'orderLinkId' => (string) $order->getId(),
                    'category' => $order->getCategory()->value,
                    'symbol' => $order->getSymbol(),
                ]
            );
        } catch (HttpException $e) {
            // Если приказа не существует всё в порядке. Он мог быть уже отменён или находится в статусе, где его нельзя отменить.
            if ($e->getCode() != ErrorCodes::ORDER_DOES_NOT_EXISTS) {
                throw $e;
            }
        }
    }
}
