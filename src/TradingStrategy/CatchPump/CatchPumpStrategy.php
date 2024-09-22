<?php

declare(strict_types=1);

namespace App\TradingStrategy\CatchPump;

use App\Entity\Coin;
use App\Entity\Order;
use App\Entity\Order\ByBit\Side;
use App\Entity\Order\Status;
use App\Entity\Position;
use App\Factory\OrderFactory;
use App\Market\Model\CandleCollection;
use App\Market\Model\CandleInterface;
use App\Market\Repository\CandleRepositoryInterface;
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
        private ?Position                          $position = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function canOpenPosition(): bool
    {
        $result = false;
        if (!$this->position) {
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
            $positionsTotalCount = $this->positionRepository->getTotalCount();
            $coinsAbleToBuy = \bcdiv($walletBalance->totalAvailableBalance, $candleLast15minutes->getClosePrice(), 4);
            $this->logger?->debug("Объём изменился на $volumeChangePercent%");
            $this->logger?->debug("Цена изменилась на $priceChangePercent%");
            $this->logger?->debug("Общее кол-во позиций $positionsTotalCount");
            $this->logger?->debug("На балансе доступно {$walletBalance->totalAvailableBalance} USDT");
            $this->logger?->debug("Можно купить $coinsAbleToBuy BTC");
            $result = \bccomp($volumeChangePercent, "30", 0) >= 0
            && \bccomp($priceChangePercent, '2', 0) >= 0
            && $coinsAbleToBuy >= '0.0001'
            && $positionsTotalCount < 5
            ;
        }
        $result ? $this->logger?->debug("Позиция может быть открыта") : null;
        return $result;
    }

    private function openPosition(PositionCanBeOpenedEvent $event): Position
    {
        $scale = 2;
        $walletBalance = $this->accountRepository->getWalletBalance();
        /** @var string $risk Риск в $ */
        $risk = bcmul('0.01', $walletBalance->totalAvailableBalance, $scale);
        $lastHourCandle = $this->candleRepository->find(symbol: $this->coin->getCode() . 'USDT', interval: '60', limit: 1);
        $stopAtr = bcdiv($lastHourCandle->getHighestPrice(), $lastHourCandle->getLowestPrice(), $scale);
        $lastTradedPrice = $this->candleRepository->getLastTradedPrice($this->coin->getCode() . 'USDT');
        $stopPrice = bcdiv($lastTradedPrice, bcmul($stopAtr, '2', $scale), $scale);
        $stopPercent = bcmul(
            bcdiv(
                bcsub($lastTradedPrice, $stopPrice, $scale),
                $lastTradedPrice,
                $scale
            ),
            "100",
            $scale
        );
        $price = bcdiv(bcmul($risk, '100', $scale), $stopPercent, $scale);
        $buyOrder = (new Order())
            ->setPrice($price)
            ->setCoin($this->coin)
            ->setSide(Order\ByBit\Side::Buy)
            ->setCategory(Order\ByBit\Category::spot)
            ->setType(Order\ByBit\Type::Market);
        $this->entityManager->persist($buyOrder);
        $this->entityManager->flush();
        // Ждём пока приказ на покупку не будет полностью выполнен
        while (sleep(5) && Status::isOpenStatus($buyOrder->getByBitStatus())) {
            $this->entityManager->refresh($buyOrder);
        }
        $stopOrder = (new Order())
            ->setTriggerPrice()//todo узнать у Котова
            ->setPrice($stopPercent)
            ->setQuantity($buyOrder->getQuantity())
            ->setCoin($this->coin)
            ->setSide(Order\ByBit\Side::Sell)
            ->setCategory(Order\ByBit\Category::spot)
            ->setType(Order\ByBit\Type::Limit)
            ->setOrderFilter(Order\ByBit\OrderFilter::StopOrder);
        $this->position = new Position();
        $this->position->setCoin($this->coin);
        $this->position->addOrder($buyOrder);
        $this->entityManager->persist($this->position);
        $this->entityManager->flush();

        return $this->position;
    }


    public static function getSubscribedEvents()
    {
        return [PositionCanBeOpenedEvent::NAME => 'openPosition'];
    }

    /**
     * Выставить приказ на продажу 50% позиции
     * @param PriceIncreased8OrMore $event
     *
     * @return void
     */
    public function sell50Percent(PriceIncreased8OrMore $event): void
    {
        assert($this->position->getOrders()->count() > 0); // Должен быть хотя бы 1 приказ на покупку
        $quantity = $this->position->getOrders()->findFirst(fn (int $key, Order $order) => $order->getSide() === Side::Buy)->getQuantity();
        $quantityForSale = bcdiv($quantity, '2', 4);
        $order = $this->orderFactory->create(coin: $this->coin, quantity: $quantityForSale, side: Side::Sell);
        $this->position->addOrder($order);
        $this->entityManager->persist($this->position);
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
        assert($this->position->getOrders()->count() > 0); // Должен быть хотя бы 1 приказ на покупку
        $quantity = $this->position->getOrders()->findFirst(fn (int $key, Order $order) => $order->getSide() === Side::Buy)->getQuantity();
        $quantityForSale = bcdiv($quantity, '4', 4);
        $order = $this->orderFactory->create(coin: $this->coin, quantity: $quantityForSale, side: Side::Sell);
        $this->position->addOrder($order);
        $this->entityManager->persist($this->position);
        $this->entityManager->flush();
    }

    /**
     * @inheritDoc
     */
    public function dispatchEvents(): void
    {
        if ($this->canOpenPosition()) {
            $this->dispatcher->dispatch(new PositionCanBeOpenedEvent(), PositionCanBeOpenedEvent::NAME);
        } elseif ($this->position?->getOrders()->first()?->getStatus() === Status::New) {
            // ждём когда приказ на покупку будет выполнен
            $this->entityManager->refresh($this->position);
        }
        if ($this->position?->getOrders()->first()?->getStatus() === Status::Filled) {
            $lastTradedPrice = $this->candleRepository->getLastTradedPrice($this->coin->getCode() . 'USDT');
            $averagePrice = $this->position->getAveragePrice();
            $priceChange = bcdiv($lastTradedPrice, $averagePrice, 2);
            if (\bccomp($priceChange, '1.08', 2) >= 0) {
                $this->dispatcher->dispatch(new PriceIncreased8OrMore());
            }
            if (\bccomp($priceChange, '1.12', 2) >= 0) {
                $this->dispatcher->dispatch(new PriceIncreased12OrMore());
            }
        }
    }
}
