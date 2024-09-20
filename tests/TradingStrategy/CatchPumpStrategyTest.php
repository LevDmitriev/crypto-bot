<?php

namespace App\Tests\TradingStrategy;

use App\Account\Model\WalletBalance;
use App\Entity\Coin;
use App\Entity\Order;
use App\Entity\Position;
use App\Market\Model\Candle;
use App\Market\Model\CandleCollection;
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

    /**
     * Кейс с успешной возможностью открытия позиции
     * @return void
     */
    public function testCanOpenPosition(): void
    {
        $candleCollection = new CandleCollection();
        for ($i = 0; $i < 26; $i++) {
            $candle = new Candle(
                startTime: 1,
                openPrice: '1',
                closePrice: '1',
                highestPrice: '100',
                lowestPrice: '1',
                volume: '1',
                turnover: '1'
            );
            $candleCollection->add($candle);
        }
        $candlePrevious15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '1',
            highestPrice: '100',
            lowestPrice: '1',
            volume: '100000',
            turnover: '1'
        );
        $candleLast15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '102',
            highestPrice: '102',
            lowestPrice: '1',
            volume: '130000',
            turnover: '1'
        );
        $candleCollection->add($candlePrevious15minutes);
        $candleCollection->add($candleLast15minutes);
        $coin = new Coin();
        $coin->setCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBallance: '1000',
            totalAvailableBalance: '300'
        ));
        $strategy = new CatchPumpStrategy(
            coin: $coin,
            entityManager: $entityManager,
            candleRepository: $candleRepository,
            positionRepository: $positionRepository,
            accountRepository: $accountRepository
        );
        self::assertTrue($strategy->canOpenPosition());
    }

    /**
     * Нельзя открыть позицию если недостаточен объём
     * @return void
     */
    public function testCanNotOpenPositionIfVolumeIsNotEnough(): void
    {
        $candleCollection = new CandleCollection();
        for ($i = 0; $i < 26; $i++) {
            $candle = new Candle(
                startTime: 1,
                openPrice: '1',
                closePrice: '1',
                highestPrice: '100',
                lowestPrice: '1',
                volume: '1',
                turnover: '1'
            );
            $candleCollection->add($candle);
        }
        $candlePrevious15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '1',
            highestPrice: '100',
            lowestPrice: '1',
            volume: '100000',
            turnover: '1'
        );
        $candleLast15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '102',
            highestPrice: '102',
            lowestPrice: '1',
            volume: '120000',
            turnover: '1'
        );
        $candleCollection->add($candlePrevious15minutes);
        $candleCollection->add($candleLast15minutes);
        $coin = new Coin();
        $coin->setCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBallance: '1000',
            totalAvailableBalance: '300'
        ));
        $strategy = new CatchPumpStrategy(
            coin: $coin,
            entityManager: $entityManager,
            candleRepository: $candleRepository,
            positionRepository: $positionRepository,
            accountRepository: $accountRepository
        );
        self::assertFalse($strategy->canOpenPosition());
    }

    /**
     * Нельзя открыть позицию если недостаточен баланс
     * @return void
     */
    public function testCanNotOpenPositionIfNotEnoughBalance(): void
    {
        $candleCollection = new CandleCollection();
        for ($i = 0; $i < 26; $i++) {
            $candle = new Candle(
                startTime: 1,
                openPrice: '1',
                closePrice: '1',
                highestPrice: '100',
                lowestPrice: '1',
                volume: '1',
                turnover: '1'
            );
            $candleCollection->add($candle);
        }
        $candlePrevious15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '1',
            highestPrice: '100',
            lowestPrice: '1',
            volume: '100000',
            turnover: '1'
        );
        $candleLast15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '102',
            highestPrice: '102',
            lowestPrice: '1',
            volume: '130000',
            turnover: '1'
        );
        $candleCollection->add($candlePrevious15minutes);
        $candleCollection->add($candleLast15minutes);
        $coin = new Coin();
        $coin->setCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBallance: '1000',
            totalAvailableBalance: '0'
        ));
        $strategy = new CatchPumpStrategy(
            coin: $coin,
            entityManager: $entityManager,
            candleRepository: $candleRepository,
            positionRepository: $positionRepository,
            accountRepository: $accountRepository
        );
        self::assertFalse($strategy->canOpenPosition());
    }

    /**
     * Нельзя открыть позицию если открыто 5 и более позиций
     * @return void
     */
    public function testCanNotOpenPositionIfTooMuchOpenedPositions(): void
    {
        $candleCollection = new CandleCollection();
        for ($i = 0; $i < 26; $i++) {
            $candle = new Candle(
                startTime: 1,
                openPrice: '1',
                closePrice: '1',
                highestPrice: '100',
                lowestPrice: '1',
                volume: '1',
                turnover: '1'
            );
            $candleCollection->add($candle);
        }
        $candlePrevious15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '1',
            highestPrice: '100',
            lowestPrice: '1',
            volume: '100000',
            turnover: '1'
        );
        $candleLast15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '102',
            highestPrice: '102',
            lowestPrice: '1',
            volume: '130000',
            turnover: '1'
        );
        $candleCollection->add($candlePrevious15minutes);
        $candleCollection->add($candleLast15minutes);
        $coin = new Coin();
        $coin->setCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $positionRepository->method('getTotalCount')->willReturn(5);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBallance: '1000',
            totalAvailableBalance: '300'
        ));
        $strategy = new CatchPumpStrategy(
            coin: $coin,
            entityManager: $entityManager,
            candleRepository: $candleRepository,
            positionRepository: $positionRepository,
            accountRepository: $accountRepository
        );
        self::assertFalse($strategy->canOpenPosition());
    }

    /**
     * Нельзя открыть позицию если цена изменилось менее чем на 2 процента
     * @return void
     */
    public function testCanNotOpenPositionIfPriceChangeLess2Percents(): void
    {
        $candleCollection = new CandleCollection();
        for ($i = 0; $i < 26; $i++) {
            $candle = new Candle(
                startTime: 1,
                openPrice: '1',
                closePrice: '1',
                highestPrice: '100',
                lowestPrice: '1',
                volume: '1',
                turnover: '1'
            );
            $candleCollection->add($candle);
        }
        $candlePrevious15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '1',
            highestPrice: '100',
            lowestPrice: '1',
            volume: '100000',
            turnover: '1'
        );
        $candleLast15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '101',
            highestPrice: '101',
            lowestPrice: '1',
            volume: '130000',
            turnover: '1'
        );
        $candleCollection->add($candlePrevious15minutes);
        $candleCollection->add($candleLast15minutes);
        $coin = new Coin();
        $coin->setCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBallance: '1000',
            totalAvailableBalance: '300'
        ));
        $strategy = new CatchPumpStrategy(
            coin: $coin,
            entityManager: $entityManager,
            candleRepository: $candleRepository,
            positionRepository: $positionRepository,
            accountRepository: $accountRepository
        );
        self::assertFalse($strategy->canOpenPosition());
    }

    /**
     * Нельзя открыть позицию если уже есть позиция
     * @return void
     */
    public function testCanNotOpenPositionIfPositionExists()
    {
        $candleCollection = new CandleCollection();
        for ($i = 0; $i < 28; $i++) {
            $candle = new Candle(
                startTime: 1,
                openPrice: '1',
                closePrice: '1',
                highestPrice: '100',
                lowestPrice: '1',
                volume: '1',
                turnover: '1'
            );
            $candleCollection->add($candle);
        }
        $coin = new Coin();
        $coin->setCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);

        $strategy = new CatchPumpStrategy(
            coin: $coin,
            entityManager: $entityManager,
            candleRepository: $candleRepository,
            positionRepository: $positionRepository,
            accountRepository: $accountRepository,
            position: $this->createMock(Position::class)
        );
        self::assertFalse($strategy->canOpenPosition());
    }

    public function testWaitAndClosePosition()
    {

    }

    public function testHasOpenedPosition()
    {

    }
}
