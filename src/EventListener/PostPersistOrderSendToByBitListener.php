<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Order;
use App\Messages\EnrichOrderFromByBitCommand;
use ByBit\SDK\ByBitApi;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsEntityListener(event: Events::postPersist, method: 'sendToByBit', entity: Order::class)]
class PostPersistOrderSendToByBitListener
{
    public function __construct(
        private ByBitApi $byBitApi,
        //        #[Autowire(service: 'app.serializer.bybit')]
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
        /** @var Order|null $order */
        if ($order && $order->getByBitStatus() === Order\ByBit\Status::New) {
            $orderArray = $this->normalizer->normalize($order, '[]');
            $response = $this->byBitApi->tradeApi()->placeOrder($orderArray);
            if (isset($response['orderLinkId'])) {
                $this->messageBus->dispatch(new EnrichOrderFromByBitCommand($order->getId()));
            } else {
                throw new \RuntimeException('Ошибка отправки в ByBit ' . json_encode($response));
            }
        }
    }
}
