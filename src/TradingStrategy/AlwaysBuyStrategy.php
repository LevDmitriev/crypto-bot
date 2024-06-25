<?php

declare(strict_types=1);

namespace App\TradingStrategy;

class AlwaysBuyStrategy implements TradingStrategyInterface
{
    /**
     * @inheritDoc
     */
    public function handleCandle(CandleInterface $candle): void
    {
        echo "Buy!" . PHP_EOL;
    }
}
