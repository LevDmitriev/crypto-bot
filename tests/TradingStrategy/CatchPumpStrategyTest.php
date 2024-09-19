<?php

namespace App\Tests\TradingStrategy;

use App\Entity\Coin;
use App\Market\Repository\CandleRepository;
use App\Market\Repository\CandleRepositoryInterface;
use App\Repository\AccountRepository;
use App\Repository\PositionRepository;
use App\TradingStrategy\CatchPumpStrategy;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\TradingStrategy\CatchPumpStrategy
 */
class CatchPumpStrategyTest extends TestCase
{

    public function testWaitAndOpenPosition()
    {
        $coin = new Coin();
        $coin->setCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $accountRepository = $this->createMock(AccountRepository::class);
        $strategy = new CatchPumpStrategy(
            coin: $coin,
            entityManager: $entityManager,
            positionRepository: $positionRepository,
            candleRepository: $candleRepository,
            accountRepository: $accountRepository
        );
        $position = $strategy->waitAndOpenPosition();
    }

    public function testWaitAndClosePosition()
    {

    }

    public function testHasOpenedPosition()
    {

    }
}
