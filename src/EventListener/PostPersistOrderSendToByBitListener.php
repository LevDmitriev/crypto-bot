<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Order;
use App\Messages\EnrichMarketOrderFromByBitCommand;
use ByBit\SDK\ByBitApi;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsEntityListener(event: Events::postPersist, method: 'sendToByBit', entity: Order::class)]
class PostPersistOrderSendToByBitListener
{
    public function __construct(
        private ByBitApi $byBitApi,
        private NormalizerInterface $normalizer,
        private MessageBusInterface $messageBus
    ) {
    }

    /**
     * Отправить сообщение с информацией по приказу
     * @param Order $order
     *
     * @return void
     * @throws \Symfony\Component\Messenger\Exception\ExceptionInterface
     */
    public function sendToByBit(Order $order): void
    {
        $orderArray = $this->normalizer->normalize($order, '[]');
        $response = $this->byBitApi->tradeApi()->placeOrder($orderArray);
        if ($order->isMarket() && $order->isCommon()) {
            $this->messageBus->dispatch(new EnrichMarketOrderFromByBitCommand($response['orderLinkId']));
        }
    }
}
