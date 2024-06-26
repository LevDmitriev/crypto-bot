<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Order;
use App\Messages\OrderCreatedMessage;
use App\Messages\OrderMessage;
use App\Messages\OrderSetStatusMessage;
use App\Repository\OrderRepository;
use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Обработчик проставления статуса приказу
 */
#[AsMessageHandler]
class SendOrderToByBitHandler
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly ByBitApi $byBitApi,
        #[Autowire(service: 'app.serializer.bybit')]
        private readonly NormalizerInterface $normalizer,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(OrderCreatedMessage $message)
    {
        /** @var Order|null $order */
        $order = $this->repository->find($message->id);
        if ($order && $order->getStatus() === Order\Status::New) {
            $orderArray = $this->normalizer->normalize($order, '[]');
            $response = $this->byBitApi->tradeApi()->placeOrder($orderArray);
            if (isset($response['retCode']) && $response['retCode'] === 0) {
                $this->messageBus->dispatch(new OrderSetStatusMessage($message->id));
            } else {
                $this->messageBus->dispatch($message);
            }
        }
    }
}
