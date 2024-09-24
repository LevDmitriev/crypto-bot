<?php

namespace App\MessageHandler;

use App\Messages\ClosePositionCommand;
use App\Repository\PositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
class ClosePositionCommandHandler
{
    public function __construct(
        private readonly WorkflowInterface $positionStateMachine,
        private readonly PositionRepository $positionRepository,
    ) { }

    public function __invoke(ClosePositionCommand $command): void
    {
        $position = $this->positionRepository->find($command->positionId);
        if ($position && $this->positionStateMachine->can($position, 'close')) {
            $this->positionStateMachine->apply($position, 'close');
        }
    }
}
