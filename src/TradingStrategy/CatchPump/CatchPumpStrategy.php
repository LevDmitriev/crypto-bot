<?php

declare(strict_types=1);

namespace App\TradingStrategy\CatchPump;

use App\Entity\Coin;
use App\Entity\Order;
use App\Entity\Order\ByBit\OrderFilter;
use App\Entity\Order\ByBit\Side;
use App\Entity\Order\ByBit\Status as ByBitStatus;
use App\Entity\Order\Status;
use App\Entity\Position;
use App\Factory\OrderFactory;
use App\Market\Model\CandleCollection;
use App\Market\Model\CandleInterface;
use App\Market\Repository\CandleRepositoryInterface;
use App\Messages\CreateOrderToPositionCommand;
use App\Repository\AccountRepository;
use App\Repository\PositionRepository;
use App\TradingStrategy\CatchPump\Event\PositionCanBeOpenedEvent;
use App\TradingStrategy\CatchPump\Event\PriceIncreased12OrMore;
use App\TradingStrategy\CatchPump\Event\PriceIncreased8OrMore;
use App\TradingStrategy\TradingStrategyInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Стратегия, которая пытается поймать момент, когда происходит
 * разгон цены монеты (pump) и пытается купить на низах и продать на верхах.
 */
class CatchPumpStrategy implements TradingStrategyInterface, EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    public function __construct(
        private readonly Coin                      $coin,
        private readonly EntityManagerInterface    $entityManager,
        private readonly CandleRepositoryInterface $candleRepository,
        private readonly PositionRepository $positionRepository,
        private readonly AccountRepository         $accountRepository,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly OrderFactory $orderFactory,
        private readonly MessageBusInterface $commandBus,
        private readonly WorkflowInterface $positionStateMachine
    ) {
    }

    /**
     * @inheritDoc
     */
    public function canOpenPosition(): bool
    {
        $result = false;
        if (!$this->positionRepository->findOneNotClosedByCoin($this->coin) && $this->positionRepository->getTotalNotClosedCount() < 5) {
            /** @var CandleCollection $candles7hours 7 часовая свечка */
            $candles7hours = $this->candleRepository->find(symbol: $this->coin->getCode() . 'USDT', interval: '15', limit: 28);
            /** @var CandleCollection $candles7hoursExceptLast15Minutes 7 часовая свечка не включая последние 15 минут */
            $candles7hoursExceptLast15Minutes = new CandleCollection($candles7hours->slice(0, 27));
            /** @var CandleInterface $candleLast15minutes Последняя 15 минутная свечка */
            $candleLast15minutes = new CandleCollection($candles7hours->slice(27, 1));
            /** @var CandleInterface $candlePrevious15minutes Предыдущая 15 минутная свечка */
            $candlePrevious15minutes = new CandleCollection($candles7hours->slice(26, 1));
            /** @var string $priceChangePercent Изменение цены */
            $priceChangePercent = bcmul(bcsub(bcdiv($candleLast15minutes->getClosePrice(), $candles7hoursExceptLast15Minutes->getHighestPrice(), 2), '1', 2), '100', 0);
            $volumeChangePercent = bcmul(bcsub(bcdiv($candleLast15minutes->getVolume(), $candlePrevious15minutes->getVolume(), 2), '1', 2), '100', 0);
            /*
            * Открываем позицию если:
            * - по монете нет открытой позиции
            * - по монете нет открытой позиции
            * - объём торгов увеличился на 30% и более
            * - цена увеличилась на 2% и более
            * - Депозита хватает, чтобы купить хотя бы 0.0001 монету(Ограничение API)
            */
            $walletBalance = $this->accountRepository->getWalletBalance();
            $coinsAbleToBuy = \bcdiv($walletBalance->totalAvailableBalance, $candleLast15minutes->getClosePrice(), 4);
            $this->logger?->debug("Объём изменился на $volumeChangePercent%");
            $this->logger?->debug("Цена изменилась на $priceChangePercent%");
            $this->logger?->debug("На балансе доступно {$walletBalance->totalAvailableBalance} USDT");
            $this->logger?->debug("Можно купить $coinsAbleToBuy BTC");
            $result = \bccomp($volumeChangePercent, "30", 0) >= 0
            && \bccomp($priceChangePercent, '2', 0) >= 0
            && $coinsAbleToBuy >= '0.0001'
            ;
            $result ? $this->logger?->debug("Позиция может быть открыта") : null;
        }
        return $result;
    }

    public function openPosition(): Position
    {
        $scale = 2;
        $walletBalance = $this->accountRepository->getWalletBalance();
        /** @var string $risk Риск в $ */
        $risk = bcmul('0.01', $walletBalance->totalWalletBallance, $scale);
        $lastHourCandle = $this->candleRepository->find(symbol: $this->coin->getCode() . 'USDT', interval: '60', limit: 1);
        $stopAtr = bcsub($lastHourCandle->getHighestPrice(), $lastHourCandle->getLowestPrice(), $scale);
        $lastTradedPrice = $this->candleRepository->getLastTradedPrice($this->coin->getCode() . 'USDT');
        $stopPrice = bcsub($lastTradedPrice, bcmul($stopAtr, '2', $scale), $scale);
        $stopPercent = bcmul(
            bcdiv(
                bcsub($lastTradedPrice, $stopPrice, $scale),
                $lastTradedPrice,
                4
            ),
            "100",
            $scale
        );
        /** @var string $quantity Кол-во USDT на которое будут куплены монеты */
        $quantity = bcdiv(bcmul($risk, '100', $scale), $stopPercent, $scale);
        $buyOrder = $this->orderFactory->create(coin: $this->coin, quantity: $quantity);
        $position = new Position();
        $position->setCoin($this->coin);
        $position->addOrder($buyOrder);
        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($position, $buyOrder, $stopPrice) {
            $entityManager->persist($position);
        });
        $this->logger?->debug("Открыта позиция c ID {$position->getId()} на $quantity USDT");
        if ($buyOrder->getStatus() === Status::Filled->value) {
            $this->commandBus->dispatch(
                new CreateOrderToPositionCommand(
                    positionId:   $position->getId(),
                    coinId:       $this->coin->getId(),
                    quantity:     $buyOrder->getCumulativeExecutedQuantity(),
                    triggerPrice: $stopPrice,
                    side:         Side::Sell,
                    orderFilter:  OrderFilter::StopOrder
                )
            );
        } else {
            $this->positionStateMachine->apply($position, 'close');
        }

        return $position;
    }


    public static function getSubscribedEvents()
    {
        return [
            PriceIncreased8OrMore::NAME => 'sell50Percent',
            PriceIncreased12OrMore::NAME => 'sell25Percent'
        ];
    }

    /**
     * Выставить приказ на продажу 50% позиции
     * @param PriceIncreased8OrMore $event
     *
     * @return void
     */
    public function sell50Percent(PriceIncreased8OrMore $event): void
    {
        assert($event->position->getOrders()->count() > 0); // Должен быть хотя бы 1 приказ на покупку
        $quantity = $event->position->getOrders()->findFirst(fn (int $key, Order $order) => $order->getSide() === Side::Buy)->getQuantity();
        $quantityForSale = bcdiv($quantity, '2', 2);
        $order = $this->orderFactory->create(coin: $this->coin, quantity: $quantityForSale, side: Side::Sell);
        $event->position->addOrder($order);
        $this->entityManager->persist($event->position);
        $this->entityManager->flush();
    }
    /**
     * Выставить приказ на продажу 25% позиции
     * @param PriceIncreased12OrMore $event
     *
     * @return void
     */
    public function sell25Percent(PriceIncreased12OrMore $event): void
    {
        assert($event->position->getOrders()->count() > 0); // Должен быть хотя бы 1 приказ на покупку
        $quantity = $event->position->getOrders()->findFirst(fn (int $key, Order $order) => $order->getSide() === Side::Buy)->getQuantity();
        $quantityForSale = bcdiv($quantity, '4', 2);
        $order = $this->orderFactory->create(coin: $this->coin, quantity: $quantityForSale, side: Side::Sell);
        $event->position->addOrder($order);
        $this->entityManager->persist($event->position);
        $this->entityManager->flush();
    }

    /**
     * @inheritDoc
     */
    public function dispatchEvents(): void
    {
        if ($this->canOpenPosition()) {
            $this->openPosition();
        }
        $allPositions = $this->positionRepository->findAll();
        foreach ($allPositions as $position) {
            if ($position?->getOrders()->first()?->getStatus() === Status::Filled->value) {
                $lastTradedPrice = $this->candleRepository->getLastTradedPrice($this->coin->getCode() . 'USDT');
                $averagePrice = $position->getAveragePrice();
                $priceChange = bcdiv($lastTradedPrice, $averagePrice, 2);
                if (\bccomp($priceChange, '1.08', 2) >= 0) {
                    $this->dispatcher->dispatch(new PriceIncreased8OrMore($position));
                }
                if (\bccomp($priceChange, '1.12', 2) >= 0) {
                    $this->dispatcher->dispatch(new PriceIncreased12OrMore($position));
                }
            }
        }
    }
}
