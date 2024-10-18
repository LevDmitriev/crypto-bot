<?php

namespace App\Scheduler\Task;

use App\Messages\HandlePositionCommand;
use App\Repository\PositionRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

/**
 * Команда отправит на обработку все открытые позиции
 */
#[AsCronTask('* * * * *', 'Europe/Moscow')]
readonly class HandleOpenedPositionsTask
{
    public function __construct(
        private PositionRepository $positionRepository,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke() : void
    {
        $positions = $this->positionRepository->findAllNotClosed();
        foreach ($positions as $position) {
            $this->commandBus->dispatch(
                new HandlePositionCommand($position->getId()),
                [new TransportMessageIdStamp("handle_position_{$position->getId()}")]
            );
        }
    }
}
