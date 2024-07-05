<?php

declare(strict_types=1);

use App\Entity\Order;
use ByBit\SDK\ByBitApi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

class StartCancellingEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ByBitApi $byBitApi)
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [TransitionEvent::getName('order', 'start_canceling') => 'startCancelling'];
    }


    public function startCancelling(TransitionEvent $event): void
    {
        $order = $event->getSubject();
        assert($order instanceof Order);
        $response = $this->byBitApi->tradeApi()->cancelOrder(
            [
                'orderLinkId' => $order->getId(),
                'category' => $order->getCategory()->value,
                'symbol' => $order->getSymbol(),
            ]
        );
        if ($response['retMsg'] !== 'OK') {
            throw new \Exception("Ошибка отмены приказа:" . $response['retMsg']);
        }
    }
}
