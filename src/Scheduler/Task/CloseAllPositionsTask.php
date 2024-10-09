<?php

namespace App\Scheduler\Task;

use App\Entity\Order\ByBit\Category;
use App\Entity\Order\ByBit\OrderFilter;
use App\Repository\PositionRepository;
use ByBit\SDK\ByBitApi;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Команда по закрытию всех позиций
 */
#[AsCronTask('50 18 * * *', 'Europe/Moscow', transports: 'async')]
readonly class CloseAllPositionsTask
{
    public function __construct(
        private WorkflowInterface $positionStateMachine,
        private PositionRepository $positionRepository,
        private ByBitApi $byBitApi
    ) {
    }

    public function __invoke()
    {
        // На всякий случай отменяем всё
        $this->byBitApi->tradeApi()->cancelAllOrders(['category' => Category::spot->value, 'orderFilter' => OrderFilter::Order->value]);
        $this->byBitApi->tradeApi()->cancelAllOrders(['category' => Category::spot->value, 'orderFilter' => OrderFilter::StopOrder->value]);
        foreach ($this->positionRepository->findAllNotClosed() as $position) {
            $this->positionStateMachine->apply($position, 'close');
        }
    }
}
