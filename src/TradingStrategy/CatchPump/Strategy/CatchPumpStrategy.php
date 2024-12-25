<?php

declare(strict_types=1);

namespace App\TradingStrategy\CatchPump\Strategy;

use App\Entity\Coin;
use App\Entity\Order\ByBit\OrderFilter;
use App\Entity\Order\ByBit\Side;
use App\Entity\Position;
use App\Factory\OrderFactory;
use App\Market\Model\CandleCollection;
use App\Market\Model\CandleInterface;
use App\Market\Repository\CandleRepositoryInterface;
use App\Repository\AccountRepository;
use App\Repository\PositionRepository;
use App\TradingStrategy\TradingStrategyInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Message\Message;
use WebSocket\Message\Text;

/**
 * Стратегия, которая пытается поймать момент, когда происходит
 * разгон цены монеты (pump) и пытается купить на низах и продать на верхах.
 */
class CatchPumpStrategy implements TradingStrategyInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    public const NAME = 'catch-pump';
    public function __construct(
        private readonly EntityManagerInterface    $entityManager,
        private readonly CandleRepositoryInterface $candleRepository,
        private readonly PositionRepository $positionRepository,
        private readonly AccountRepository         $accountRepository,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly OrderFactory $orderFactory,
        private readonly MessageBusInterface $commandBus,
        private readonly WorkflowInterface $positionStateMachine,
        private readonly WorkflowInterface $catchPumpPositionStateMachine
    ) {
    }

    /**
     * @inheritDoc
     */
    private function canOpenPosition(Coin $coin): bool
    {
        $result = false;
        /*
        * Открываем позицию если:
        * - Менее 5 открытых позиций
        * - по монете нет открытой позиции
        * - объём торгов увеличился на 30% и более
        * - цена увеличилась на 2% и более
        * - За весь период разница между самой высокой и самой низкой ценой была <= 5%
        */
        if (!$this->positionRepository->findOneNotClosedByCoin($coin) && $this->positionRepository->getTotalNotClosedCount() < 5) {
            /** @var int $hoursCount Количество часов за которые считаем свечки */
            $hoursCount = 20;
            /** @var CandleCollection $candles Свечки за все часы по 15 минут каждая в коллекции */
            $candles = $this->candleRepository->find(symbol: $coin->getId() . 'USDT', interval: '15', limit: $hoursCount * 4);
            /** @var CandleCollection $candlesExceptLast15Minutes Свечки не включая последние 15 минут */
            $candlesExceptLast15Minutes = new CandleCollection($candles->slice(1, $hoursCount * 4 - 1));
            /** @var CandleInterface $candleLast15minutes Последняя 15 минутная свечка */
            $candleLast15minutes = $candles->first();
            /** @var CandleInterface $candlePrevious15minutes Предыдущая 15 минутная свечка */
            $candlePrevious15minutes = $candles->get(1);
            // Если объём торгов и цена увеличились
            if (bccomp($candlePrevious15minutes->getVolume(), '0', 4) > 0 && bccomp($candlesExceptLast15Minutes->getHighestPrice(), '0', 6) > 0) {
                /** @var string $priceChangePercent Изменение цены */
                $priceChangePercent = bcmul(bcsub(bcdiv($candleLast15minutes->getClosePrice(), $candlesExceptLast15Minutes->getHighestPrice(), 6), '1', 2), '100', 0);
                $volumeChangePercent = bcmul(bcsub(bcdiv($candleLast15minutes->getVolume(), $candlePrevious15minutes->getVolume(), 4), '1', 4), '100', 0);
                /** @var string $volatilePercent Процент насколько самая высокая цена больше самой низкой цены. 2 знака после запятой. */
                $volatilePercent = bcmul(bcsub(bcdiv($candles->getHighestPrice(), $candles->getLowestPrice(), 6), '1', 4), '100', 2);

                /** @var bool $isVolumeChangedEnough Объёи изменился достаточно? */
                $isVolumeChangedEnough = \bccomp($volumeChangePercent, "30", 0) >= 0;
                /** @var bool $isPriceChangedEnough Цена изменилась достаточно? */
                $isPriceChangedEnough = \bccomp($priceChangePercent, '2', 0) >= 0;
                /** @var bool $isVolatileEnough Волатильность достаточная? */
                $isVolatileEnough = \bccomp('5', $volatilePercent, 2) >= 0;
                $result = $isVolumeChangedEnough && $isPriceChangedEnough && $isVolatileEnough;
                $message = "Обработана монета {$coin->getId()}. ";
                if (!$isPriceChangedEnough) {
                    $message .= "Цена изменилась на $priceChangePercent";
                } elseif (!$isVolumeChangedEnough) {
                    $message .= "Объём изменился на $volumeChangePercent";
                } elseif (!$isVolatileEnough) {
                    $message .= "Волатильность $priceChangePercent";
                } else {
                    $message .= "Позиция может быть открыта";
                }
                $this->logger?->info($message);
            }
        }
        return $result;
    }

    private function openPosition(Coin $coin): Position
    {
        $scale = 6;
        $walletBalance = $this->accountRepository->getWalletBalance();
        /** @var string $risk Риск в $ */
        $risk = bcmul('0.01', $walletBalance->totalEquity, $scale);
        $lastHourCandle = $this->candleRepository->find(symbol: $coin->getId() . 'USDT', interval: '60', limit: 1);
        $stopAtr = bcsub($lastHourCandle->getHighestPrice(), $lastHourCandle->getLowestPrice(), $scale);
        $lastTradedPrice = $this->candleRepository->getLastTradedPrice($coin->getId() . 'USDT');
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
        $buyOrder = $this->orderFactory->create(coin: $coin, quantity: $quantity);
        $position = new Position();
        $position->setCoin($coin);
        $position->addOrder($buyOrder);
        $position->setStrategyName(static::NAME);
        $this->entityManager->beginTransaction();
        $this->entityManager->persist($position);
        $this->entityManager->persist($buyOrder);
        $this->entityManager->flush();
        $stopOrder = $this->orderFactory->create(
            coin: $position->getCoin(),
            quantity: $buyOrder->getRealExecutedQuantity(),
            triggerPrice: $stopPrice,
            side: Side::Sell,
            orderFilter:  OrderFilter::StopOrder
        );
        $position->addOrder($stopOrder);
        $this->positionStateMachine->apply($position, 'open');
        $this->entityManager->persist($position);
        $this->entityManager->persist($stopOrder);
        $this->entityManager->flush();
        $this->entityManager->commit();

        return $position;
    }


    public function openPositionIfPossible(Coin $coin): ?Position
    {
        if ($this->canOpenPosition($coin)) {
            return $this->openPosition($coin);
        }

        return null;
    }

    public function handlePosition(Position $position): void
    {
        $topic = "kline.1.{$position->getCoin()->getId()}USDT";
        $client = new Client("wss://stream.bybit.com/v5/public/spot");
        $client->send(new Text(json_encode(["op" => "subscribe", 'args' => [$topic]])));
        $client
            ->setPersistent(true)
            ->onText(function (Client $client, Connection $connection, Message $message) use ($position) {
                $json = json_decode($message->getContent(), true);
                if ($lastTradedPrice = $json['data'][0]['close'] ?? null) {
                    $averagePrice = $position->getAveragePrice();
                    $priceChangePercent = (float)bcmul(
                        bcsub(bcdiv($lastTradedPrice, $averagePrice, 6), '1', 6),
                        '100',
                        2
                    );
                    $previousPosition = $position->getStatusInStrategy();
                    if ($priceChangePercent >= 2 && $this->catchPumpPositionStateMachine->can($position, 'increase_2')) {
                        $this->catchPumpPositionStateMachine->apply($position, 'increase_2');
                    }
                    if ($priceChangePercent >= 8 && $this->catchPumpPositionStateMachine->can($position, 'increase_8')) {
                        $this->catchPumpPositionStateMachine->apply($position, 'increase_8');
                    }
                    if ($priceChangePercent >= 12 && $this->catchPumpPositionStateMachine->can($position, 'increase_12')) {
                        $this->catchPumpPositionStateMachine->apply($position, 'increase_12');
                    }
                    if ($priceChangePercent >= 13 && $this->catchPumpPositionStateMachine->can($position, 'increase_13')) {
                        $this->catchPumpPositionStateMachine->apply($position, 'increase_13');
                    }
                    if ($previousPosition !== $position->getStatusInStrategy()) {
                        $this->entityManager->persist($position);
                        $this->entityManager->flush();
                    }
                    $this->entityManager->refresh($position);
                }
            })
            ->onTick(function (Client $client) use ($position) {
                if ($position->isClosed()) {
                    $client->stop();
                    $client->disconnect();
                }
            })
            ->start();
    }
}
