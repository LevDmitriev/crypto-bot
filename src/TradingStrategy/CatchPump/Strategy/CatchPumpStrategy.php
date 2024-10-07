<?php

declare(strict_types=1);

namespace App\TradingStrategy\CatchPump\Strategy;

use App\Entity\Coin;
use App\Entity\Order;
use App\Entity\Order\ByBit\OrderFilter;
use App\Entity\Order\ByBit\Side;
use App\Entity\Position;
use App\Entity\Position\Status;
use App\Factory\OrderFactory;
use App\Market\Model\CandleCollection;
use App\Market\Model\CandleInterface;
use App\Market\Repository\CandleRepositoryInterface;
use App\Messages\ClosePositionCommand;
use App\Messages\CreateOrderToPositionCommand;
use App\Repository\AccountRepository;
use App\Repository\PositionRepository;
use App\TradingStrategy\CatchPump\Event\LastTwoHoursPriceChangedEvent;
use App\TradingStrategy\TradingStrategyInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Стратегия, которая пытается поймать момент, когда происходит
 * разгон цены монеты (pump) и пытается купить на низах и продать на верхах.
 */
class CatchPumpStrategy implements TradingStrategyInterface, EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    public const NAME = 'catch-pump';
    public function __construct(
        private readonly Coin                      $coin,
        private readonly EntityManagerInterface    $entityManager,
        private readonly CandleRepositoryInterface $candleRepository,
        private readonly PositionRepository $positionRepository,
        private readonly AccountRepository         $accountRepository,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly OrderFactory $orderFactory,
        private readonly MessageBusInterface $commandBus,
        private readonly WorkflowInterface $positionStateMachine,
        private readonly LockFactory $lockFactory
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
            $candles7hours = $this->candleRepository->find(symbol: $this->coin->getByBitCode() . 'USDT', interval: '15', limit: 28);
            /** @var CandleCollection $candles7hoursExceptLast15Minutes 7 часовая свечка не включая последние 15 минут */
            $candles7hoursExceptLast15Minutes = new CandleCollection($candles7hours->slice(0, 27));
            /** @var CandleInterface $candleLast15minutes Последняя 15 минутная свечка */
            $candleLast15minutes = new CandleCollection($candles7hours->slice(27, 1));
            /** @var CandleInterface $candlePrevious15minutes Предыдущая 15 минутная свечка */
            $candlePrevious15minutes = new CandleCollection($candles7hours->slice(26, 1));
            if (bccomp($candlePrevious15minutes->getVolume(), '0', 4) > 0 && bccomp($candles7hoursExceptLast15Minutes->getHighestPrice(),'0', 6) > 0) {
                /** @var string $priceChangePercent Изменение цены */
                $priceChangePercent = bcmul(bcsub(bcdiv($candleLast15minutes->getClosePrice(), $candles7hoursExceptLast15Minutes->getHighestPrice(), 6), '1', 2), '100', 0);
                $volumeChangePercent = bcmul(bcsub(bcdiv($candleLast15minutes->getVolume(), $candlePrevious15minutes->getVolume(), 4), '1', 4), '100', 0);

                /*
                * Открываем позицию если:
                * - по монете нет открытой позиции
                * - объём торгов увеличился на 30% и более
                * - цена увеличилась на 2% и более
                * - Депозита хватает, чтобы купить хотя бы 0.0001 монету(Ограничение API)
                */
                $walletBalance = $this->accountRepository->getWalletBalance();
                $coinsAbleToBuy = \bcdiv($walletBalance->totalAvailableBalance, $candleLast15minutes->getClosePrice(), 4);
                /** @var bool $isVolumeChangedEnough Объёи изменился достаточно? */
                $isVolumeChangedEnough = \bccomp($volumeChangePercent, "3", 0) >= 0;
                /** @var bool $isPriceChangedEnough Цена изменилась достаточно? */
                $isPriceChangedEnough = \bccomp($priceChangePercent, '1', 0) >= 0;
                /** @var bool $isAbleToBuyEnoughCoins Можно купить достаточно монет? */
                $isAbleToBuyEnoughCoins = \bccomp($coinsAbleToBuy,'0.0001', 4) >= 0;
                $result = $isVolumeChangedEnough && $isPriceChangedEnough && $isAbleToBuyEnoughCoins;
                $message = "Обработана монета {$this->coin->getByBitCode()}. ";
                if (!$isPriceChangedEnough) {
                    $message .= "Цена изменилась на $priceChangePercent";
                } elseif (!$isVolumeChangedEnough) {
                    $message .= "Объём изменился на $volumeChangePercent";
                } elseif (!$isAbleToBuyEnoughCoins) {
                    $message .= "Количество монет к покупке $coinsAbleToBuy";
                } else {
                    $message .= "Позиция может быть открыта";
                }
                $this->logger?->warning($message);
            }
        }
        return $result;
    }

    public function openPosition(): Position
    {
        $scale = 6;
        $walletBalance = $this->accountRepository->getWalletBalance();
        /** @var string $risk Риск в $ */
        $risk = bcmul('0.01', $walletBalance->totalWalletBallance, $scale);
        $lastHourCandle = $this->candleRepository->find(symbol: $this->coin->getByBitCode() . 'USDT', interval: '60', limit: 1);
        $stopAtr = bcsub($lastHourCandle->getHighestPrice(), $lastHourCandle->getLowestPrice(), $scale);
        $lastTradedPrice = $this->candleRepository->getLastTradedPrice($this->coin->getByBitCode() . 'USDT');
        $stopPrice = bcsub($lastTradedPrice, bcmul($stopAtr, '2', $scale), $scale);
        $stopPercent = bcmul(
            bcdiv(
                bcsub($lastTradedPrice, $stopPrice, $scale),
                $lastTradedPrice,
                $scale
            ),
            "100",
            $scale
        );
        /** @var string $quantity Кол-во USDT на которое будут куплены монеты */
        $quantity = bcdiv(bcmul($risk, '100', $scale), $stopPercent, $scale);
        //$quantity = '7';
        $buyOrder = $this->orderFactory->create(coin: $this->coin, quantity: $quantity);
        $position = new Position();
        $position->setCoin($this->coin);
        $position->addOrder($buyOrder);
        $position->setStrategyName(static::NAME);
        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($position, $buyOrder, $stopPrice) {
            $entityManager->persist($position);
        });
        if ($buyOrder->isFilled()) {
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


    public static function getSubscribedEvents(): array
    {
        return [
            LastTwoHoursPriceChangedEvent::NAME => ['moveStopPlusPoint2','sell50Percent', 'sell25Percent', 'moveStopPlus10point2']
        ];
    }

    /**
     * Увеличить триггерную цену стопа на 0,2% от изначальной
     * @param LastTwoHoursPriceChangedEvent $event
     *
     * @return void
     */
    public function moveStopPlusPoint2(LastTwoHoursPriceChangedEvent $event): void
    {
        $this->entityManager->wrapInTransaction(function () use ($event) {
            $lockKey = self::NAME."-position-price-increased-2percent-{$event->position->getId()}";
            $lock = $this->lockFactory->createLock($lockKey, 7200, false);
            if ($event->changePercent >= 2 && $lock->acquire()) {
                $orders = $event->position->getOrders();
                $buyOrder = $orders->filterBuyOrders()->first();
                $stopOrder = $orders->filterStopOrders()->first();
                $triggerPrice = bcmul($buyOrder->getAveragePrice(), '1.002', 4);
                if ($stopOrder->getTriggerPrice() !== $triggerPrice) {
                    $stopOrder->setTriggerPrice($triggerPrice);
                    $this->entityManager->persist($stopOrder);
                }
            }
        });
    }

    /**
     * Выставить приказ на продажу 50% позиции
     * @param LastTwoHoursPriceChangedEvent $event
     *
     * @return void
     */
    public function sell50Percent(LastTwoHoursPriceChangedEvent $event): void
    {
        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($event) {
            $lockKey = self::NAME."-position-price-increased-8percent-{$event->position->getId()}";
            $lock = $this->lockFactory->createLock($lockKey, 7200, false);
            if ($event->changePercent >= 8 && $lock->acquire()) {
                $orders = $event->position->getOrders();
                $buyOrder = $orders->filterBuyOrders()->first();
                $quantityForSale = bcdiv($buyOrder->getCumulativeExecutedQuantity(), '2', 4);
                $stopOrder = $orders->filterStopOrders()->first();
                $stopOrder->setTriggerPrice(bcmul($buyOrder->getAveragePrice(), '1.02', 4));
                $order = $this->orderFactory->create(coin: $this->coin, quantity: $quantityForSale, side: Side::Sell);
                $event->position->addOrder($order);
                $entityManager->persist($event->position);
            }
        });

    }
    /**
     * Выставить приказ на продажу 25% позиции
     * @param LastTwoHoursPriceChangedEvent $event
     *
     * @return void
     */
    public function sell25Percent(LastTwoHoursPriceChangedEvent $event): void
    {
        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($event) {
            $lockKey = self::NAME."-position-price-increased-12percent-{$event->position->getId()}";
            $lock = $this->lockFactory->createLock($lockKey, 7200, false);
            if ($event->changePercent >= 12 && $lock->acquire()) {
                $orders = $event->position->getOrders();
                $buyOrder = $orders->filterBuyOrders()->first();
                $quantityForSale = bcdiv($buyOrder->getQuantity(), '4', 4);
                $stopOrder = $orders->filterStopOrders()->first();
                $stopOrder->setTriggerPrice(bcmul($buyOrder->getAveragePrice(), '1.082', 4));
                $order = $this->orderFactory->create(coin: $this->coin, quantity: $quantityForSale, side: Side::Sell);
                $event->position->addOrder($order);
                $entityManager->persist($event->position);
            }
        });
    }

    /**
     * Увеличить триггерную цену стопа на 10,2% от изначальной
     * @param LastTwoHoursPriceChangedEvent $event
     *
     * @return void
     */
    public function moveStopPlus10point2(LastTwoHoursPriceChangedEvent $event): void
    {
        $this->entityManager->wrapInTransaction(function () use ($event) {
            $lockKey = self::NAME."-position-price-increased-13percent-{$event->position->getId()}";
            $lock = $this->lockFactory->createLock($lockKey, 7200, false);
            if ($event->changePercent >= 13 && $lock->acquire()) {
                $orders = $event->position->getOrders();
                $buyOrder = $orders->filterBuyOrders()->first();
                $stopOrder = $orders->filterStopOrders()->first();
                $triggerPrice = bcmul($buyOrder->getAveragePrice(), '1.102', 4);
                $stopOrder->setTriggerPrice($triggerPrice);
                $this->entityManager->persist($stopOrder);
                $this->entityManager->flush();
            }
        });
        $now = new \DateTime('now', 'Europe/Moscow');
        $today1850 = new \DateTime('today 18:50', new \DateTimeZone('Europe/Moscow'));
        $delay = $today1850->getTimestamp() - $now->getTimestamp();
        if ($delay) { // Если 18:50 ещё не наступило, то отправляем команду закрытия позиции на это время
            $this->commandBus->dispatch(new ClosePositionCommand($event->position->getId()), [new DelayStamp($delay * 1000)]);
        }
    }

    /**
     * @inheritDoc
     */
    public function dispatchEvents(): void
    {
        if ($this->coin->getByBitCode() !== "DOME") {
            return;
        }
        if ($this->canOpenPosition()) {
            $this->openPosition();
        }
        $criteria = new Criteria();
        $criteria->andWhere(Criteria::expr()->neq('status', Status::Closed->value));
        $criteria->andWhere(Criteria::expr()->eq('coin', $this->coin));
        $criteria->andWhere(Criteria::expr()->eq('strategyName', static::NAME));
        $openedPositions = $this->positionRepository->matching($criteria);
        /** @var Position $position */
        foreach ($openedPositions as $position) {
            $twoHours = 7200;
            /** @var bool $is2HoursExpired Истекло 2 часа от входа в позицию?  */
            $is2HoursExpired = (time() - $position->getCreatedAt()->getTimestamp()) > $twoHours;
            if (!$is2HoursExpired) {
                $lastTradedPrice = $this->candleRepository->getLastTradedPrice($this->coin->getByBitCode() . 'USDT');
                $averagePrice = $position->getAveragePrice();
                $priceChangePercent = (float) bcmul(bcsub(bcdiv($lastTradedPrice, $averagePrice, 2), '1', 2), '100', 2);
                $this->dispatcher->dispatch(new LastTwoHoursPriceChangedEvent($position, $priceChangePercent));
            }
        }
    }
}
