<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Position;
use App\Entity\Position\Status;
use App\Repository\PositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * Команда запускающая подпроцессы на обработку каждой позиции
 */
class HandlePositionsCommand extends Command
{
    public function __construct(
        private readonly PositionRepository $positionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct('app:positions:handle');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        /** @var ArrayCollection<string, Process> $runningProcesses Map ID позиции и её процесс */
        $runningProcesses = new ArrayCollection();
        while (true) {
            // Ищем все открытые позиции, которые ещё не обрабатываем
            $criteria = Criteria::create()->andWhere(Criteria::expr()->eq('status', Status::Opened));
            if ($runningProcesses->count()) {
                $criteria->andWhere(Criteria::expr()->notIn('id', $runningProcesses->getKeys()));
            }
            /** @var Collection<int, Position> $positions */
            $positions = $this->positionRepository->matching($criteria);
            foreach ($positions as $position) {
                $symfonyStyle->writeln( (new \DateTime())->format('Y-m-d\TH:i:sO') . " Запуск обработки позиции {$position->getId()} ");
                $subProcess = new Process(["bin/console", "app:position:handle", $position->getId()], timeout: 0);
                $subProcess->start(function (string $type, string $buffer) use ($symfonyStyle): void {
                    if (Process::ERR === $type) {
                        $symfonyStyle->error($buffer);
                    } else {
                        $symfonyStyle->write($buffer);
                    }
                });
                $runningProcesses->set($position->getId(), $subProcess);
            }
            $runningProcesses = $runningProcesses->filter(fn (Process $process) => $process->isRunning());
            $this->entityManager->clear();
            sleep(5); // хотя бы 5 секунд подождём чтобы не долбить постоянно БД
        }

        return self::SUCCESS;
    }
}
