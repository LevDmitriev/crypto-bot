<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Order;
use App\Entity\Order\ByBit\OrderFilter;
use App\Entity\Order\ByBit\Status as ByBitStatus;
use App\Messages\EnrichMarketOrderFromByBitCommand;
use App\Messages\EnrichOrderFromByBitCommand;
use App\Repository\OrderRepository;
use App\Repository\PositionRepository;
use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Обработчик ожидает пока приказ будет выполнен и обогощает его данными из ByBit.
 */
#[AsMessageHandler]
readonly class EnrichOrderFromByBitHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ByBitApi $byBitApi,
        private EntityManagerInterface $entityManager,
        private DenormalizerInterface $denormalizer,
        private MessageBusInterface $commandBus
    ) {
    }

    public function __invoke(EnrichOrderFromByBitCommand $message)
    {
        /** @var Order|null $order */
        $order = $this->orderRepository->find($message->id);
        if ($order && $order->getByBitStatus() === ByBitStatus::New) {
            $orderFromApi = $this->byBitApi->tradeApi()->getOpenOrders(['orderLinkId' => $message->id, 'category' => 'spot']);
            $orderFromApi = isset($orderFromApi['list'][0]) ? $orderFromApi['list'][0] : $orderFromApi;
            if (isset($orderFromApi['orderStatus']) && ByBitStatus::isClosedStatus(ByBitStatus::from($orderFromApi['orderStatus']))) {
                $order = $this->denormalizer->denormalize($orderFromApi, Order::class, '[]', [AbstractNormalizer::OBJECT_TO_POPULATE => $order]);
                $this->entityManager->persist($order);
                return;
            }
        }
        $this->commandBus->dispatch(new RedispatchMessage($message), [new DelayStamp(5000)]);
    }
}
