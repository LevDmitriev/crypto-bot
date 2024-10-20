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
                'symbol' => $order->getCoin()->getId() . 'USDT',
                'category' => $order->getCategory()->value,
            ];
            // value это массив где 0 элемент - прошлое значение, а 1 элемент - новое значение
            foreach ($changeSet as $field => $value) {
                switch ($field) {
                    case "quantity": $orderArray['qty'] = $value[1];
                        break;
                    case "price": $orderArray['price'] = $value[1];
                        break;
                    case "triggerPrice": $orderArray['triggerPrice'] = $value[1];
                        break;
                }
            }
            $this->amendOrder($orderArray);
        }
    }

    private function amendOrder(array $order): void
    {
        try {
            $this->byBitApi->tradeApi()->amendOrder($order);
        } catch (HttpException $e) {
            /*
             * У разных монет есть своя кратность и можем получить ошибку что слишком много символов после запятой.
             * todo вынести в настройки монеты
             * Пока что постепенно отрезаем числа после запятой
             */
            if ($e->getCode() === ErrorCodes::ORDER_QUANTITY_HAS_TOO_MANY_DECIMALS || $e->getCode() === ErrorCodes::ORDER_QUANTITY_HAS_TOO_MANY_DECIMALS_AMEND) {
                $order['qty'] = substr($order['qty'], 0, -1);
                $this->amendOrder($order);
                return;
            }
            /*
                 * Есть очень специфическая незадокументированная ошибка.
                 * Если отправить приказ на обновление, но в нём ничего не изменить,
                 * возвращается ошибка 10001, но у неё совершенно другое сообщение об ошибке:
                 * The order remains unchanged as the parameters entered match the existing ones.
                 * Игнорируем такую ошибку
                 */
            if ($e->getCode() === ErrorCodes::NOT_SUPPORTED_SYMBOLS) {
                return ;
            }
            throw $e;
        }
    }
}
