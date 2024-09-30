<?php

declare(strict_types=1);

namespace App\Command;

use App\Bybit\ErrorCodes;
use App\Entity\Coin;
use App\Repository\CoinRepository;
use App\TradingStrategy\TradingStrategyFactoryInterface;
use ByBit\SDK\Exceptions\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Команда торговли с помощью торговой стратегии
 */
class TradeCommand extends Command
{
    public function __construct(
        private readonly TradingStrategyFactoryInterface $tradingStrategyFactory,
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
                foreach ($coins as $coin) {
                    $strategy = $this->tradingStrategyFactory->create($input->getArgument('strategy'), $coin);
                    try {
                        $strategy->dispatchEvents();
                    } catch (HttpException $exception) {
                        if ($exception->getCode() !== ErrorCodes::NOT_SUPPORTED_SYMBOLS) {
                            throw $exception;
                        }
                    }
                    $this->entityManager->clear();
                }
                sleep(60*15);
        }

        return self::SUCCESS;
    }
}
