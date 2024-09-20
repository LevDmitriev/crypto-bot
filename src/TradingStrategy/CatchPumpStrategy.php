<?php

declare(strict_types=1);

namespace App\TradingStrategy;

use App\Entity\Coin;
use App\Entity\Order;
use App\Entity\Order\ByBit\Status;
use App\Entity\Position;
use App\Market\Model\CandleCollection;
use App\Market\Model\CandleInterface;
use App\Market\Repository\CandleRepositoryInterface;
use App\Repository\AccountRepository;
use App\Repository\PositionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Стратегия, которая пытается поймать момент, когда происходит
 * разгон цены монеты (pump) и пытается купить на низах и продать на верхах.
 */
class CatchPumpStrategy implements TradingStrategyInterface
{
    public function __construct(
        private readonly Coin                      $coin,
        private readonly EntityManagerInterface    $entityManager,
        private readonly CandleRepositoryInterface $candleRepository,
        private readonly PositionRepository $positionRepository,
        private readonly AccountRepository         $accountRepository,
        private ?Position                          $position = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function canOpenPosition(): bool
    {
        /** @var CandleCollection $candles7hours 7 часовая свечка */
        $candles7hours = $this->candleRepository->find(symbol: $this->coin->getCode() . 'USDT', interval: '15', limit: 28);
        /** @var CandleCollection $candles7hoursExceptLast15Minutes 7 часовая свечка не включая последние 15 минут */
        $candles7hoursExceptLast15Minutes = new CandleCollection($candles7hours->slice(0, 27));
        /** @var CandleInterface $candleLast15minutes Последняя 15 минутная свечка */
        $candleLast15minutes = new CandleCollection($candles7hours->slice(27, 1));
        /** @var CandleInterface $candlePrevious15minutes Предыдущая 15 минутная свечка */
        $candlePrevious15minutes = new CandleCollection($candles7hours->slice(26, 1));
        /** @var string $priceChange Изменение цены */
        $priceChange = bcdiv($candleLast15minutes->getClosePrice(), $candles7hoursExceptLast15Minutes->getHighestPrice(), 2);
        $volumeChange = bcdiv($candleLast15minutes->getVolume(), $candlePrevious15minutes->getVolume(), 2);
        /*
        * Открываем позицию если:
        * - по монете нет открытой позиции
        * - по монете нет открытой позиции
        * - объём торгов увеличился на 30% и более
        * - цена увеличилась на 2% и более
        * - Депозита хватает чтобы купить хотя бы 0.0001 монету(Ограничение API)
        */
        $walletBalance = $this->accountRepository->getWalletBalance();
        return !$this->hasOpenedPosition()
            && bccomp($volumeChange, "1.3", 2) >= 0
            && bccomp($priceChange, '1.02', 2) >= 0
            && bcdiv($walletBalance->totalAvailableBalance, $candleLast15minutes->getClosePrice(), 4) >= '0.0001'
            && $this->positionRepository->getTotalCount() < 5
        ;
    }

    public function openPosition(): Position
    {
        assert(!$this->position);
        $scale = 2;
        $deposit = $this->accountRepository->getDeposit();
        /** @var string $risk Риск в $ */
        $risk = bcmul('0.01', $deposit, $scale);
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
        $this->position = new Position($buyOrder);
        $this->position->setStopOrder($stopOrder);
        $this->entityManager->persist($this->position);
        $this->entityManager->flush();

        return $this->position;
    }

    public function сlosePosition(): Position
    {
        if ($this->position->isClosed()) {
            return $this->position;
        }

        return $this->position;
    }

    public function hasOpenedPosition(): bool
    {
        return (bool)$this->position?->isOpened();
    }


    private function createPosition(string $currentPrice): Position
    {

    }
}
