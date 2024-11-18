<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Order\ByBit\Category;
use App\Entity\Order\ByBit\OrderFilter;
use App\Messages\ClosePositionCommand;
use App\Repository\PositionRepository;
use App\Scheduler\Task\CloseAllPositionsTask;
use ByBit\SDK\ByBitApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

/**
 * Консольная команда закрытия всех позиций
 */
class CloseAllPositionsCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly PositionRepository $positionRepository,
        private readonly ByBitApi $byBitApi
    ) {
        parent::__construct('app:close-all-positions');
    }

    protected function configure()
    {
        $this->setDescription('Закрыть все позиции');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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
        return self::SUCCESS;
    }
}
