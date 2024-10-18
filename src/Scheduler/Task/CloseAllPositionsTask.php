<?php

namespace App\Scheduler\Task;

use App\Entity\Order\ByBit\Category;
use App\Entity\Order\ByBit\OrderFilter;
use App\Messages\ClosePositionCommand;
use App\Repository\PositionRepository;
use ByBit\SDK\ByBitApi;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

/**
 * Команда по закрытию всех позиций
 */
#[AsCronTask('50 18 * * *', 'Europe/Moscow', transports: 'async')]
readonly class CloseAllPositionsTask
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private PositionRepository $positionRepository,
        private ByBitApi $byBitApi
    ) {
    }

    public function __invoke() : void
    {
        // На всякий случай отменяем всё
        $this->byBitApi->tradeApi()->cancelAllOrders(['category' => Category::spot->value, 'orderFilter' => OrderFilter::Order->value]);
        $this->byBitApi->tradeApi()->cancelAllOrders(['category' => Category::spot->value, 'orderFilter' => OrderFilter::StopOrder->value]);
        foreach ($this->positionRepository->findAllNotClosed() as $position) {
            $this->commandBus->dispatch(
                new ClosePositionCommand($position->getId()),
                [new TransportMessageIdStamp("close_position_{$position->getId()}")]
            );
        }
    }
}
