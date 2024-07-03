<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Order;
use App\Entity\Order\ByBit\Status as ByBitStatus;
use App\Messages\OrderEnrichFromByBitMessage;
use App\Repository\OrderRepository;
use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Обработчик проставления статуса приказу
 */
#[AsMessageHandler]
class OrderEnrichFromBybitHandler
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly ByBitApi $byBitApi,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        #[Autowire(service:'app.serializer.bybit')]
        private readonly DenormalizerInterface $denormalizer
    ) {
    }

    public function __invoke(OrderEnrichFromByBitMessage $message)
    {
        /** @var Order|null $order */
        $order = $this->repository->find($message->id);
        if ($order && $order->getByBitStatus() === ByBitStatus::New) {
            $orderFromApi = $this->byBitApi->tradeApi()->getOpenOrders(['orderLinkId' => $message->id, 'category' => 'spot']);
            $orderFromApi = isset($orderFromApi['list'][0]) ? $orderFromApi['list'][0] : $orderFromApi;
            if (isset($orderFromApi['orderStatus']) && ByBitStatus::isClosedStatus(ByBitStatus::from($orderFromApi['orderStatus']))) {
                $order = $this->denormalizer->denormalize($orderFromApi, Order::class, '[]', [AbstractNormalizer::OBJECT_TO_POPULATE => $order]);
                $this->entityManager->persist($order);
                $this->entityManager->flush();
                return;
            }
        }
        $this->messageBus->dispatch($message, [new DelayStamp(5000)]);
    }
}
