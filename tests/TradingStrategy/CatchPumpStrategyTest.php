<?php

namespace App\Tests\TradingStrategy;

use App\Account\Model\WalletBalance;
use App\Entity\Coin;
use App\Entity\Order;
use App\Entity\Order\ByBit\Type;
use App\Entity\Position;
use App\Factory\OrderFactory;
use App\Market\Model\Candle;
use App\Market\Model\CandleCollection;
use App\Market\Repository\CandleRepositoryInterface;
use App\Repository\AccountRepository;
use App\Repository\PositionRepository;
use App\TradingStrategy\CatchPump\Event\PriceIncreased12OrMore;
use App\TradingStrategy\CatchPump\Event\PriceIncreased8OrMore;
use App\TradingStrategy\CatchPump\Strategy\CatchPumpStrategy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
        $coin->setByBitCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBallance: '1000',
            totalAvailableBalance: '300'
        ));
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class)
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
        $coin->setByBitCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBallance: '1000',
            totalAvailableBalance: '300'
        ));
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class)
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
        $coin->setByBitCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBallance: '1000',
            totalAvailableBalance: '0'
        ));
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class)
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
        $coin->setByBitCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $positionRepository->method('getTotalNotClosedCount')->willReturn(5);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBallance: '1000',
            totalAvailableBalance: '300'
        ));
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class)
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
        $coin->setByBitCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn(new WalletBalance(
            totalWalletBallance: '1000',
            totalAvailableBalance: '300'
        ));
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class)
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
        $coin->setByBitCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $candleRepository->method('find')->willReturn($candleCollection);
        $walletBalance = new WalletBalance('500', '1000');
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn($walletBalance);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory:       new OrderFactory(),
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class)
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
        $coin = new Coin();
        $coin->setByBitCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $accountRepository = $this->createMock(AccountRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $buyOrder = new Order();
        $buyOrder->setCoin($coin)
            ->setQuantity('2')
            ->setSide(Order\ByBit\Side::Buy)
        ;
        $position = new Position();
        $position->setCoin($coin);
        $position->addOrder($buyOrder);
        $position->addOrder(new Order());
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory: $orderFactory,
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class)
        );
        $entityManager->expects(self::once())->method('persist')->with($position);
        $entityManager->expects(self::once())->method('flush');
        $strategy->sell50Percent(new PriceIncreased8OrMore($position));
        $order = $position->getOrders()->last();
        self::assertEquals('1.00', $order->getQuantity());
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
        $coin = new Coin();
        $coin->setByBitCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $positionRepository = $this->createMock(PositionRepository::class);
        $candleRepository = $this->createMock(CandleRepositoryInterface::class);
        $accountRepository = $this->createMock(AccountRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $buyOrder = new Order();
        $buyOrder->setCoin($coin)
            ->setQuantity('4')
            ->setSide(Order\ByBit\Side::Buy)
        ;
        $position = new Position();
        $position->setCoin($coin);
        $position->addOrder($buyOrder);
        $position->addOrder(new Order());
        $position->addOrder(new Order());
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory: $orderFactory,
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class)
        );
        $entityManager->expects(self::once())->method('persist')->with($position);
        $entityManager->expects(self::once())->method('flush');
        $strategy->sell25Percent(new PriceIncreased12OrMore($position));
        $order = $position->getOrders()->last();
        self::assertEquals('1.00', $order->getQuantity());
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
        $coin = new Coin();
        $coin->setByBitCode('BTC');
        $entityManager = $this->createMock(EntityManagerInterface::class);
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
        $walletBalance = new WalletBalance('1000', '500');
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository->method('getWalletBalance')->willReturn($walletBalance);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $strategy = new CatchPumpStrategy(
            coin:               $coin,
            entityManager:      $entityManager,
            candleRepository:   $candleRepository,
            positionRepository: $positionRepository,
            accountRepository:  $accountRepository,
            dispatcher:         $dispatcher,
            orderFactory: $orderFactory,
            commandBus: $this->createMock(MessageBusInterface::class),
            positionStateMachine: $this->createMock(WorkflowInterface::class)
        );
        $position = $strategy->openPosition();
        $buyOrder = $position->getOrders()->first();
        self::assertEquals('51.02', $buyOrder->getQuantity());
        self::assertEquals(Type::Market, $buyOrder->getType());
    }
}
