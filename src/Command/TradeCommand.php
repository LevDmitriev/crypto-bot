<?php

declare(strict_types=1);

namespace App\Command;

use App\Bybit\ErrorCodes;
use App\Entity\Coin;
use App\Repository\CoinRepository;
use App\TradingStrategy\TradingStrategyRepositoryInterface;
use ByBit\SDK\Exceptions\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Команда торговли с помощью торговой стратегии
 */
class TradeCommand extends Command
{
    public function __construct(
        private readonly TradingStrategyRepositoryInterface $tradingStrategyFactory,
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
        while (true) {
            $coins = $this->coinRepository->createQueryBuilder('c')->getQuery()->toIterable();
            /** @var Coin $coin */
            foreach ($coins as $coin) {
                $strategy = $this->tradingStrategyFactory->get($input->getArgument('strategy'));
                try {
                    $strategy->openPositionIfPossible($coin);
                } catch (HttpException $exception) {
                    if (!in_array($exception->getCode(), [ErrorCodes::NOT_SUPPORTED_SYMBOLS, ErrorCodes::INVALID_SERVER_TIMESTAMP])) {
                        throw $exception;
                    }
                    $output->writeln("{$coin->getId()}: {$exception->getMessage()}");
                }
                $this->entityManager->detach($coin);
            }
            $this->entityManager->clear();
            gc_collect_cycles();
        }

        return self::SUCCESS;
    }
}
