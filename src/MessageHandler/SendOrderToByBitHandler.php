<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Order;
use App\Messages\SendOrderToByBitCommand;
use App\Messages\OrderMessage;
use App\Messages\EnrichOrderFromByBitCommand;
use App\Repository\PositionRepository;
use ByBit\SDK\ByBitApi;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Обработчик проставления статуса приказу
 */
#[AsMessageHandler]
readonly class SendOrderToByBitHandler
{
    public function __construct(
        private PositionRepository $repository,
        private ByBitApi $byBitApi,
        //        #[Autowire(service: 'app.serializer.bybit')]
        private NormalizerInterface $normalizer,
        private MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(SendOrderToByBitCommand $message)
    {
        /** @var Order|null $order */
        $order = $this->repository->find($message->id);
        if ($order && $order->getByBitStatus() === Order\ByBit\Status::New) {
            $orderArray = $this->normalizer->normalize($order, '[]');
            $response = $this->byBitApi->tradeApi()->placeOrder($orderArray);
            if (isset($response['orderLinkId'])) {
                $this->messageBus->dispatch(new EnrichOrderFromByBitCommand($message->id));
            } else {
                $this->messageBus->dispatch(new RedispatchMessage($message));
            }
        }
    }
}
