<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Position;
use App\Repository\CoinRepository;
use App\Repository\PositionRepository;
use App\TradingStrategy\Candle;
use App\TradingStrategy\TradingStrategyFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Message\Message;
use WebSocket\Message\Text;

/**
 * Команда торговли с помощью торговой стратегии
 */
class TradeCommand extends Command
{
    public function __construct(
        private readonly TradingStrategyFactoryInterface $tradingStrategyFactory,
        private readonly CoinRepository $coinRepository,
        private readonly PositionRepository $positionRepository,
    ) {
        parent::__construct('app:trade');
    }
    protected function configure()
    {
        $this->addOption('strategy', 's', mode: InputOption::VALUE_REQUIRED, default: 'always-buy');
        $this->addOption('coin', 'c', mode: InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $coin = $input->getOption('coin');
        $coin = $this->coinRepository->findOneBy(['code' => $coin]);
        while ($coin && $this->positionRepository->getTotalCount() < 5) {
            $strategy = $this->tradingStrategyFactory->create($input->getOption('strategy'), $coin);
            if (!$strategy->hasOpenedPosition()) {
                $strategy->waitAndOpenPosition();
            }
            $strategy->waitAndClosePosition();
        }

        return self::SUCCESS;
    }
}
