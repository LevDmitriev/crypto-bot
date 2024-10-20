<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Order;
use App\Entity\Order\ByBit\Status as ByBitStatus;
use App\Messages\EnrichOrderFromByBitCommand;
use App\Repository\OrderRepository;
use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Обработчик проставления статуса приказу
 */
#[AsMessageHandler]
readonly class EnrichOrderFromByBitHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ByBitApi $byBitApi,
        private EntityManagerInterface $entityManager,
        private DenormalizerInterface $denormalizer
    ) {
    }

    public function __invoke(EnrichOrderFromByBitCommand $message)
    {
        /** @var Order|null $order */
        $order = $this->orderRepository->find($message->id);
        if ($order) {
            $orderFromApi = $this->byBitApi->tradeApi()->getOpenOrders(['orderLinkId' => $message->id, 'category' => 'spot']);
            if (!isset($orderFromApi['list'][0])) {
                $orderFromApi = $this->byBitApi->tradeApi()->getOrderHistory(['orderLinkId' => $message->id, 'category' => 'spot']);
            }
            $orderFromApi = isset($orderFromApi['list'][0]) ? $orderFromApi['list'][0] : $orderFromApi;
            if (isset($orderFromApi['orderStatus']) && ByBitStatus::isClosedStatus(ByBitStatus::from($orderFromApi['orderStatus']))) {
                $order = $this->denormalizer->denormalize($orderFromApi, Order::class, '[]', [AbstractNormalizer::OBJECT_TO_POPULATE => $order]);
                if ($this->entityManager->getUnitOfWork()->getEntityChangeSet($order)) {
                    $this->entityManager->persist($order);
                }
                return;
            }
            throw new RecoverableMessageHandlingException("Ждём когда приказ будет выполнен");
        }
    }
}
