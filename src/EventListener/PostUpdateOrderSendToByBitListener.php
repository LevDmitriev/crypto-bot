<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Bybit\ErrorCodes;
use App\Entity\Order;
use App\Entity\Order\Status;
use App\Messages\EnrichOrderFromByBitCommand;
use ByBit\SDK\ByBitApi;
use ByBit\SDK\Exceptions\HttpException;
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
        $changeSet = $this->entityManager->getUnitOfWork()->getEntityChangeSet($order);
        $fieldsNames = array_keys($changeSet);
        // Обновляем в ByBit только если действительно произошли изменения
        if ($order->isNew() && (
            in_array('quantity', $fieldsNames, true)
            || in_array('price', $fieldsNames, true)
            || in_array('triggerPrice', $fieldsNames, true)
        )) {
            $orderArray = [
                'orderLinkId' => (string) $order->getId(),
                'symbol' => $order->getCoin()->getCode() . 'USDT',
                'category' => $order->getCategory()->value,
            ];

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

            try {
                $this->byBitApi->tradeApi()->amendOrder($orderArray);
            } catch (HttpException $exception) {
                /*
                 * Есть очень специфическая незадокументированная ошибка.
                 * Если отправить приказ на обновление, но в нём ничего не изменить,
                 * возвращается ошибка 10001, но у неё совершенно другое сообщение об ошибке:
                 * The order remains unchanged as the parameters entered match the existing ones.
                 * Игнорируем такую ошибку
                 */
                if ($exception->getCode() !== ErrorCodes::NOT_SUPPORTED_SYMBOLS) {
                    throw $exception;
                }
            }
        }
    }
}
