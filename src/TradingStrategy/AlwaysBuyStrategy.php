<?php

declare(strict_types=1);

namespace App\TradingStrategy;

use App\Entity\Coin;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

class AlwaysBuyStrategy implements TradingStrategyInterface
{
    private Candle $lastCandleWhenBought;
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @inheritDoc
     */
    public function handleCandle(CandleInterface $candle): void
    {
        if (isset($this->lastCandleWhenBought) && $this->lastCandleWhenBought->getStartTime() != $candle->getStartTime()) {
            $order = new Order();
            $order->setCoin(new Coin());
            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $this->lastCandleWhenBought = $candle;
            echo "Buy!" . PHP_EOL;
        }
    }
}
