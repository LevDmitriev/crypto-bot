<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Order;
use App\Messages\EnrichMarketOrderFromByBitCommand;
use ByBit\SDK\ByBitApi;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsEntityListener(event: Events::postUpdate, method: 'sendToByBit', entity: Order::class)]
readonly class PostUpdateOrderSendToByBitListener
{
    public function __construct(
        private ByBitApi $byBitApi,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
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
        if ($order->getStatus() === Order\Status::New->value) {
            $orderArray = [
                'orderLinkId' => $order->getId(),
                'symbol' => $order->getCoin()->getCode() . 'USDT',
                'category' => $order->getCategory()->value,
            ];
            $changeSet = $this->entityManager->getUnitOfWork()->getEntityChangeSet($order);
            foreach ($changeSet as $field => $value) {
                switch ($field) {
                    case "quantity": $orderArray['qty'] = $value;
                    break;
                    case "price": $orderArray['price'] = $value;
                    break;
                    case "triggerPrice": $orderArray['triggerPrice'] = $value;
                    break;
                }
            }
            $response = $this->byBitApi->tradeApi()->amendOrder($orderArray);
            if ($order->isMarket() && $order->isCommon()) {
                $this->messageBus->dispatch(new EnrichMarketOrderFromByBitCommand($response['orderLinkId']));
            }
        }
    }
}
