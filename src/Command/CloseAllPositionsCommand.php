<?php

declare(strict_types=1);

namespace App\Command;

use App\Scheduler\Task\CloseAllPositionsTask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Консольная команда закрытия всех позиций
 */
class CloseAllPositionsCommand extends Command
{
    public function __construct(
        private readonly CloseAllPositionsTask $task,
    ) {
        parent::__construct('app:close-all-positions');
    }

    protected function configure()
    {
        $this->setDescription('Закрыть все позиции');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->task->__invoke();
        return self::SUCCESS;
    }
}
