<?php

declare(strict_types=1);

namespace App\Command;

use App\Bybit\ErrorCodes;
use App\Repository\CoinRepository;
use App\TradingStrategy\TradingStrategyRepositoryInterface;
use ByBit\SDK\Exceptions\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Команда торговли с помощью торговой стратегии
 */
class OpenPositionIfPossibleCommand extends Command
{
    public function __construct(
        private readonly TradingStrategyRepositoryInterface $tradingStrategyFactory,
        private readonly CoinRepository $coinRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct('app:open-position-if-possible');
    }
    protected function configure()
    {
        $this
            ->addArgument('strategy', InputArgument::REQUIRED)
            ->addArgument('coin', InputArgument::REQUIRED)
            ->setDescription("В соответствии с торговой стратегией открыть позицию, если возможно")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $coin = $this->coinRepository->find($input->getArgument('coin'));
        $strategy = $this->tradingStrategyFactory->get($input->getArgument('strategy'));
        try {
            $strategy->openPositionIfPossible($coin);
        } catch (HttpException $exception) {
            if (!in_array($exception->getCode(), [ErrorCodes::NOT_SUPPORTED_SYMBOLS, ErrorCodes::INVALID_SERVER_TIMESTAMP])) {
                throw $exception;
            }
            $output->writeln("{$coin->getId()}: {$exception->getMessage()}");
        }
        return self::SUCCESS;
    }
}
