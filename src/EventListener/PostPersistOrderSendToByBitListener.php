<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Bybit\ErrorCodes;
use App\Entity\Order;
use App\Messages\EnrichOrderFromByBitCommand;
use ByBit\SDK\ByBitApi;
use ByBit\SDK\Exceptions\HttpException;
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
        $response = $this->placeOrder($orderArray);
        if ($order->isMarket() && $order->isCommon()) {
            $this->messageBus->dispatch(new EnrichOrderFromByBitCommand($response['orderLinkId']));
        }
    }

    private function placeOrder(array $order): array
    {
        try {
            return $this->byBitApi->tradeApi()->placeOrder($order);
        } catch (HttpException $e) {
            /*
             * У разных монет есть своя кратность и можем получить ошибку что слишком много символов после запятой.
             * todo вынести в настройки монеты
             * Пока что постепенно отрезаем числа после запятой
             */
            if ($e->getCode() === ErrorCodes::ORDER_QUANTITY_HAS_TOO_MANY_DECIMALS) {
                $order['qty'] = substr($order['qty'], 0, -1);
                return $this->placeOrder($order);
            }
            throw $e;
        }
    }
}
