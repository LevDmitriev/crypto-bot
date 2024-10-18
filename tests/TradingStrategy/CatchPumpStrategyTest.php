<?php

namespace App\Tests\TradingStrategy;

use App\Account\Model\WalletBalance;
use App\Entity\Order;
use App\Entity\Order\ByBit\Type;
use App\Entity\Position;
use App\Factory\OrderFactory;
use App\Market\Model\Candle;
use App\Market\Model\CandleCollection;
use App\Market\Repository\CandleRepositoryInterface;
use App\Repository\AccountRepository;
use App\Repository\CoinRepository;
use App\Repository\PositionRepository;
use App\TradingStrategy\CatchPump\Event\LastTwoHoursPriceChangedEvent;
use App\TradingStrategy\CatchPump\Strategy\CatchPumpStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \App\TradingStrategy\CatchPump\Strategy\CatchPumpStrategy
 */
class CatchPumpStrategyTest extends KernelTestCase
{
    /**
     * Кейс с успешной возможностью открытия позиции
     * @return void
     */
    public function testCanOpenPosition(): void
    {
        $candleCollection = new CandleCollection();
        $candleLast15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '102',
            highestPrice: '102',
            lowestPrice: '1',
            volume: '130000',
            turnover: '1'
        );
        $candlePrevious15minutes = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '1',
            highestPrice: '100',
            lowestPrice: '1',
            volume: '100000',
            turnover: '1'
        );
        $candleCollection->add($candleLast15minutes);
        $candleCollection->add($candlePrevious15minutes);
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

        $coinRepository = self::getContainer()->get(CoinRepository::class);
        $coin = $coinRepository->findByByBitCode('BTC');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBalance:    '300',
            totalAvailableBalance: '300',
            totalEquity: '1000'
        ));
        $lockFactory = new LockFactory(new InMemoryStore());
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class),
            lockFactory: $lockFactory,
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
            volume: '101000',
            turnover: '1'
        );
        $candleCollection->add($candlePrevious15minutes);
        $candleCollection->add($candleLast15minutes);
        $coinRepository = self::getContainer()->get(CoinRepository::class);
        $coin = $coinRepository->findByByBitCode('BTC');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBalance:    '300',
            totalAvailableBalance: '300',
            totalEquity: '1000'
        ));
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $lockFactory = new LockFactory(new InMemoryStore());
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class),
            lockFactory: $lockFactory
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
        $coinRepository = self::getContainer()->get(CoinRepository::class);
        $coin = $coinRepository->findByByBitCode('BTC');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBalance:    '0',
            totalAvailableBalance: '0',
            totalEquity: '1000'
        ));
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $lockFactory = new LockFactory(new InMemoryStore());
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class),
            lockFactory:         $lockFactory
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
        $coinRepository = self::getContainer()->get(CoinRepository::class);
        $coin = $coinRepository->findByByBitCode('BTC');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $positionRepository->method('getTotalNotClosedCount')->willReturn(5);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBalance:    '300',
            totalAvailableBalance: '300',
            totalEquity: '1000',
        ));
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $lockFactory = new LockFactory(new InMemoryStore());
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class),
            lockFactory: $lockFactory
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
            closePrice: '100',
            highestPrice: '100',
            lowestPrice: '1',
            volume: '130000',
            turnover: '1'
        );
        $candleCollection->add($candlePrevious15minutes);
        $candleCollection->add($candleLast15minutes);
        $coinRepository = self::getContainer()->get(CoinRepository::class);
        $coin = $coinRepository->findByByBitCode('BTC');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBalance:    '300',
            totalAvailableBalance: '300',
            totalEquity: '1000',
        ));
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $lockFactory = new LockFactory(new InMemoryStore());
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class),
            lockFactory: $lockFactory
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
        $coinRepository = self::getContainer()->get(CoinRepository::class);
        $coin = $coinRepository->findByByBitCode('BTC');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $walletBalance = new WalletBalance('500', '500', totalEquity: '1000');
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn($walletBalance);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $lockFactory = new LockFactory(new InMemoryStore());
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class),
            lockFactory: $lockFactory
        );
        self::assertFalse($strategy->canOpenPosition());
    }

    /**
     * @return void
     * @covers ::sell50Percent
     */
    public function testSell50Percent(): void
    {
        $orderFactory = new OrderFactory();
        $coinRepository = self::getContainer()->get(CoinRepository::class);
        $coin = $coinRepository->findByByBitCode('BTC');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $accountRepository = $this->createMock(AccountRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $buyOrder = new Order();
        $buyOrder->setCoin($coin)
            ->setQuantity('2')
            ->setAveragePrice('100')
            ->setCumulativeExecutedQuantity('2')
            ->setCumulativeExecutedFee('0')
            ->setSide(Order\ByBit\Side::Buy)
        ;
        $position = new Position();
        $position->setCoin($coin);
        $position->setStrategyName(CatchPumpStrategy::NAME);
        $position->addOrder($buyOrder);
        $stopOrder = (new Order())
            ->setCoin($coin)
            ->setQuantity('2')
            ->setSide(Order\ByBit\Side::Sell)
            ->setOrderFilter(Order\ByBit\OrderFilter::StopOrder)
            ;
        $position->addOrder($stopOrder);
        $lockFactory = new LockFactory(new InMemoryStore());
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory: $orderFactory,
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class),
            lockFactory: $lockFactory
        );
        $strategy->sell50Percent(new LastTwoHoursPriceChangedEvent($position, 8));
        $order = $position->getOrdersCollection()->last();
        self::assertEquals('1.0000', $order->getQuantity());
        self::assertEquals('102.0000', $stopOrder->getTriggerPrice());
        self::assertEquals($coin, $order->getCoin());
        self::assertEquals(Type::Market, $order->getType());
    }
    /**
     * @return void
     * @covers ::sell25Percent
     */
    public function testSell25Percent(): void
    {
        $orderFactory = new OrderFactory();
        $coinRepository = self::getContainer()->get(CoinRepository::class);
        $coin = $coinRepository->findByByBitCode('BTC');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $accountRepository = $this->createMock(AccountRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $buyOrder = new Order();
        $buyOrder->setCoin($coin)
            ->setQuantity('4')
            ->setAveragePrice('1000')
            ->setSide(Order\ByBit\Side::Buy)
        ;
        $position = new Position();
        $position->setCoin($coin);
        $position->setStrategyName(CatchPumpStrategy::NAME);
        $position->addOrder($buyOrder);
        $stopOrder = (new Order())
            ->setCoin($coin)
            ->setQuantity('2')
            ->setSide(Order\ByBit\Side::Sell)
            ->setOrderFilter(Order\ByBit\OrderFilter::StopOrder)
        ;
        $position->addOrder($stopOrder);
        $lockFactory = new LockFactory(new InMemoryStore());
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory: $orderFactory,
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class),
            lockFactory: $lockFactory
        );
        $strategy->sell25Percent(new LastTwoHoursPriceChangedEvent($position, 12));
        $order = $position->getOrdersCollection()->last();
        self::assertEquals('1.0000', $order->getQuantity());
        self::assertEquals('1082.0000', $stopOrder->getTriggerPrice());
        self::assertEquals($coin, $order->getCoin());
        self::assertEquals(Type::Market, $order->getType());
    }

    /**
     * @return void
     * @covers ::openPosition
     */
    public function testOpenPosition(): void
    {
        $orderFactory = new OrderFactory();
        $coinRepository = self::getContainer()->get(CoinRepository::class);
        $coin = $coinRepository->findByByBitCode('BTC');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('getLastTradedPrice')->willReturn('102');
        $candle = new Candle(
            startTime: 1,
            openPrice: '1',
            closePrice: '1',
            highestPrice: '100',
            lowestPrice: '90',
            volume: '1',
            turnover: '1'
        );
        $candleRepository->method('find')->willReturn(new CandleCollection([$candle]));
        $walletBalance = new WalletBalance('500', '500', totalEquity: '1000');
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn($walletBalance);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $lockFactory = new LockFactory(new InMemoryStore());
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory: $orderFactory,
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class),
            lockFactory: $lockFactory
        );
        $position = $strategy->openPosition();
        $buyOrder = $position->getOrdersCollection()->first();
        self::assertEquals('51.000112', $buyOrder->getQuantity());
        self::assertEquals(Type::Market, $buyOrder->getType());
    }
}
