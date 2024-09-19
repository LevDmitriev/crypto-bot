<?php

declare(strict_types=1);

namespace App\TradingStrategy;

use App\Entity\Coin;
use App\Entity\Order;
use App\Entity\Order\ByBit\Status;
use App\Entity\Position;
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
        private readonly PositionRepository        $positionRepository,
        private readonly CandleRepositoryInterface $candleRepository,
        private AccountRepository                  $accountRepository,
        private ?Position                          $position = null
    ) {
    }

    public function waitAndOpenPosition(): Position
    {
        assert(!$this->position);
        while (!$this->position && sleep(60) === 0) {
            /** @var CandleInterface $candles7hours 7 часовая свечка */
            $candles7hours = $this->candleRepository->find(symbol: $this->coin->getCode() . 'USDT', interval: '1', limit: 420);
            $candles30minutes = $this->candleRepository->find(symbol: $this->coin->getCode() . 'USDT', interval: '1', limit: 30);
            /** @var CandleInterface $candle15minutes Последняя 15 минутная свечка */
            $candle15minutes = $candles30minutes->slice(0, 15);
            /** @var CandleInterface $candle30to15minutes Предыдущая 15 минутная свечка */
            $candle30to15minutes = $candles30minutes->slice(14, 15);
            /** @var string $priceChange Изменение цены */
            $priceChange = bcdiv($candle15minutes->getClosePrice(), $candles7hours->getHighestPrice(), 2);
            /*
            * Открываем позицию если:
            * - объём торгов увеличился на 30% и более
            * - цена увеличилась на 2%
            * - Депозита хватает чтобы купить хотя бы 0.0001 монету(Ограничение API)
            */
            $volumeChange = bcdiv($candle15minutes->getVolume(), $candle30to15minutes->getVolume(), 2);
            $deposit = $this->accountRepository->getDeposit();
            if (bccomp($volumeChange, "1.3") >= 0 && bccomp($priceChange, '1.02') >= 0 && bcdiv($candle15minutes->getClosePrice(), $deposit) >= '0.0001') {
                $this->position = $this->createPosition($candle15minutes->getClosePrice());
                $this->entityManager->persist($this->position);
                $this->entityManager->flush();
            }
        }

        return $this->position;
    }

    public function waitAndClosePosition(): Position
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
        $scale = 2;
        $deposit = $this->accountRepository->getDeposit();
        /** @var string $risk Риск в $ */
        $risk = bcmul('0.01', $deposit, $scale);
        $lastHourCandle = $this->candleRepository->find(symbol: $this->coin->getCode() . 'USDT', interval: '60', limit: 1);
        $stopAtr = bcdiv($lastHourCandle->getHighestPrice(), $lastHourCandle->getLowestPrice(), $scale);
        $stopPrice = bcdiv($currentPrice, bcmul($stopAtr, '2', $scale), $scale);
        $stopPercent = bcmul(
            bcdiv(
                bcsub($currentPrice, $stopPrice, $scale),
                $currentPrice,
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

        $position = new Position($buyOrder);
        $position->setStopOrder($stopOrder);

        return $position;
    }
}
