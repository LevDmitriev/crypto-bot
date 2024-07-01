<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Order;
use App\Messages\OrderMessage;
use App\Messages\OrderSetStatusMessage;
use App\Repository\OrderRepository;
use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Обработчик проставления статуса приказу
 */
#[AsMessageHandler]
class OrderStatusHandler
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly ByBitApi $byBitApi,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(OrderSetStatusMessage $message)
    {
        /** @var Order|null $order */
        $order = $this->repository->find($message->id);
        if ($order && $order->getByBitStatus() === Order\ByBit\Status::New) {
            $orderFromApi = $this->byBitApi->tradeApi()->getOpenOrders(['orderLinkId' => $message->id]);
            if (isset($orderFromApi['orderStatus']) && $orderFromApi['orderStatus'] !== Order\ByBit\Status::New) {
                $order->setByBitStatus($orderFromApi['orderStatus']);
                $this->entityManager->persist($order);
                $this->entityManager->flush();
                return;
            }
        }
        $this->messageBus->dispatch($message, [new DelayStamp(5000)]);
    }
}
