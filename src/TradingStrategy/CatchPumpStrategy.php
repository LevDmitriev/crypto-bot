<?php

declare(strict_types=1);

namespace App\TradingStrategy;

use App\Entity\Coin;
use App\Entity\Order;
use App\Entity\Position;
use App\Repository\AccountRepository;
use App\Repository\PositionRepository;
use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Message\Message;
use WebSocket\Message\Text;

/**
 * Стратегия, которая пытается поймать момент, когда происходит
 * разгон цены монеты (pump) и пытается купить на низах и продать на верхах.
 */
class CatchPumpStrategy implements TradingStrategyInterface
{
    private ?Position $position = null;

    public function __construct(
        private readonly Coin                   $coin,
        private readonly EntityManagerInterface $entityManager,
        private readonly PositionRepository     $positionRepository,
        private readonly DenormalizerInterface  $denormalizer,
        private readonly ByBitApi               $byBitApi,
        private AccountRepository               $accountRepository
    ) {
        $this->position = $this->positionRepository->findOneByCoin($this->coin);
    }

    public function waitAndOpenPosition(): Position
    {
        while (!$this->position && sleep(60) === 0) {
            $klines7hours = $this->byBitApi->marketApi()->getKline([
                'category' => 'spot',
                'symbol' => $this->coin->getCode() . 'USDT',
                'interval' => 1,
                'limit' => 420
            ]);
            /** @var CandleInterface $candles7hours 7 часовая свечка */
            $candles7hours = $this->createCandlesCollection($klines7hours['list']);
            $kline30minutes = $this->byBitApi->marketApi()->getKline([
                'category' => 'spot',
                'symbol' => $this->coin->getCode() . 'USDT',
                'interval' => 1,
                'limit' => 30
            ]);
            /** @var CandleInterface $candles15minutes Последняя 15 минутная свечка */
            $candles15minutes = $this->createCandlesCollection(array_slice($kline30minutes['list'], 0, 15));
            /** @var CandleInterface $candles30to15minutes Предыдущая 15 минутная свечка */
            $candles30to15minutes = $this->createCandlesCollection(array_slice($kline30minutes['list'], 14, 15));
            /** @var string $priceChange Изменение цены */
            $priceChange = bcdiv($candles15minutes->getClosePrice(), $candles7hours->getHighestPrice(), 2);
            // Если объем торгов увеличился на 30% и более и цена увеличилась более чем на 2% - открываем позицию
            $volumeChange = (float)bcdiv($candles15minutes->getVolume(), $candles30to15minutes->getVolume(), 2);
            if ($volumeChange > 1.3 && bccomp($priceChange, '1.02') >= 0) {
                $this->position = $this->createPosition();
                $this->entityManager->persist($this->position);
                $this->entityManager->flush();
            }
        }

        return $this->position;
    }

    public function waitAndClosePosition(): Position
    {
        // TODO: Implement waitAndClosePosition() method.
    }

    public function hasOpenedPosition(): bool
    {
        return (bool)$this->position?->isOpened();
    }

    private function createCandlesCollection(array $klines): CandleInterface
    {
        $candles = new CandleCollection();
        foreach ($klines as $kline) {
            $kline = array_combine([
                'start',
                'open',
                'high',
                'low',
                'close',
                'volume',
                'turnover'
            ], $kline);
            $kline['end'] = $kline['start'] + 3600 * 1000;
            $candles->add($this->denormalizer->denormalize($kline, Candle::class));
        }

        return $candles;
    }

    private function createPosition(string $currentPrice): Position
    {
        $scale = 2;
        $deposit = $this->accountRepository->getDeposit();
        $risk = bcmul('0.01', $deposit, $scale);
        $lastHourCandle = $this->byBitApi->marketApi()->getKline([
            'category' => 'spot',
            'symbol' => $this->coin->getCode() . 'USDT',
            'interval' => 60,
            'limit' => 1
        ]);
        $lastHourCandle = $this->createCandlesCollection($lastHourCandle['list']);
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
        $order = (new Order())
            ->setPrice($price)
            ->setSide(Order\ByBit\Side::Buy)
            ->setCategory(Order\ByBit\Category::spot)
            ->setType(Order\ByBit\Type::Market)
            ->setStopLossPrice($stopPrice)
        ;
        $position = new Position($order);

        return $position;
    }
}
