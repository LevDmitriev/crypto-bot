<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Coin;
use App\Repository\CoinRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\PhpSubprocess;

/**
 * Команда торговли с помощью торговой стратегии
 */
class TradeCommand extends Command
{
    public function __construct(
        private readonly CoinRepository $coinRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct('app:trade');
    }
    protected function configure()
    {
        $this->addArgument('strategy', InputArgument::REQUIRED)
            ->setDescription("Запустить торговлю всеми монетами по указанной стратегии")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $runningProcesses = new ArrayCollection();
        while (true) {
            $coins = $this->coinRepository->createQueryBuilder('c')->getQuery()->toIterable();
            /** @var Coin $coin */
            foreach ($coins as $coin) {
                $subProcess = new PhpSubprocess(["bin/console", "app:open-position-if-possible", $input->getArgument('strategy'), $coin->getId()], timeout: 0);
                $subProcess->start(function (string $type, string $buffer) use ($symfonyStyle): void {
                        $symfonyStyle->write($buffer);
                });
                $runningProcesses->add($subProcess);
                $this->entityManager->detach($coin);
                do {
                    $runningProcesses = $runningProcesses->filter(fn (PhpSubprocess $process) => $process->isRunning());
                } while ($runningProcesses->count() >= 10);
            }
            $this->entityManager->clear();
            gc_collect_cycles();
        }

        return self::SUCCESS;
    }
}
