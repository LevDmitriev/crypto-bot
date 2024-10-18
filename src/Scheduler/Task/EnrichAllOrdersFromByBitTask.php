<?php

namespace App\Scheduler\Task;

use App\Entity\Order;
use App\Entity\Order\ByBit\Status as ByBitStatus;
use App\Repository\OrderRepository;
use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Команда по актуализации статуса всех приказов, которые пока в статусе Новый
 */
#[AsCronTask('* * * * *', 'Europe/Moscow')]
readonly class EnrichAllOrdersFromByBitTask
{
    public function __construct(
        private OrderRepository $orderRepository,
        private DenormalizerInterface $denormalizer,
        private EntityManagerInterface $entityManager,
        private ByBitApi $byBitApi,
    ) {
    }

    public function __invoke() : void
    {
        $orders = $this->orderRepository->findBy(['byBitStatus' =>  ByBitStatus::New]);
        foreach ($orders as $order) {
            $orderFromApi = $this->byBitApi->tradeApi()->getOpenOrders(['orderLinkId' => (string) $order->getId(), 'category' => 'spot']);
            $orderFromApi = isset($orderFromApi['list'][0]) ? $orderFromApi['list'][0] : $orderFromApi;
            if (isset($orderFromApi['orderStatus']) && ByBitStatus::isClosedStatus(ByBitStatus::from($orderFromApi['orderStatus']))) {
                $order = $this->denormalizer->denormalize($orderFromApi, Order::class, '[]', [AbstractNormalizer::OBJECT_TO_POPULATE => $order]);
                $this->entityManager->persist($order);
            }
        }
    }
}
