<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Coin;
use App\Repository\CoinRepository;
use App\Repository\PositionRepository;
use App\TradingStrategy\TradingStrategyFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Команда торговли с помощью торговой стратегии
 */
class TradeCommand extends Command
{
    public function __construct(
        private readonly TradingStrategyFactoryInterface $tradingStrategyFactory,
        private readonly CoinRepository $coinRepository,
    ) {
        parent::__construct('app:trade');
    }
    protected function configure()
    {
        $this->addArgument('strategy', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $coins = $this->coinRepository->findAll();
        $strategies = array_map(fn (Coin $coin) => $this->tradingStrategyFactory->create($input->getArgument('strategy'), $coin), $coins);
        while (true) {
            foreach ($strategies as $strategy) {
                $strategy->dispatchEvents();
            }
            sleep(60);
        }

        return self::SUCCESS;
    }
}
