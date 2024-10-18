<?php

namespace App\Scheduler\Task;

use App\Entity\Order;
use App\Entity\Order\ByBit\Status as ByBitStatus;
use App\Entity\Position\Status;
use App\Messages\HandlePositionCommand;
use App\Repository\OrderRepository;
use App\Repository\PositionRepository;
use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Команда отправит на обработку все открытые позиции
 */
#[AsCronTask('* * * * *', 'Europe/Moscow')]
readonly class SendCommandsToHandleOpenedPositionsTask
{
    public function __construct(
        private PositionRepository $positionRepository,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke()
    {
        $positions = $this->positionRepository->findAllNotClosed();
        foreach ($positions as $position) {
            $this->commandBus->dispatch(new HandlePositionCommand($position->getId()));
        }
    }
}
