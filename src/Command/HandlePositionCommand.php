<?php

declare(strict_types=1);

namespace App\Command;

use App\Market\Model\Candle;
use App\Repository\PositionRepository;
use App\TradingStrategy\TradingStrategyRepositoryInterface;
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
 * Команда запускающая обработку одной позиции
 */
class HandlePositionCommand extends Command
{
    public function __construct(
        private readonly TradingStrategyRepositoryInterface $tradingStrategyFactory,
        private readonly PositionRepository $positionRepository,
    ) {
        parent::__construct('app:position:handle');
    }

    protected function configure()
    {
        $this->addArgument('position');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $position = $this->positionRepository->find($input->getArgument('position'));
        $strategy = $this->tradingStrategyFactory->get($position->getStrategyName());
        $strategy->handlePosition($position);
        return self::SUCCESS;
    }
}
