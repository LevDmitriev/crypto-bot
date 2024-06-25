<?php

declare(strict_types=1);

namespace App\TradingStrategy;

/**
 * Свеча
 */
class Candle implements CandleInterface
{
    public function __construct(
        private readonly int $startTime,
        private readonly int $endTime,
        private readonly string $openPrice,
        private readonly string $closePrice,
        private readonly string $highestPrice,
        private readonly string $lowestPrice,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    /**
     * @inheritDoc
     */
    public function getEndTime(): int
    {
        return $this->endTime;
    }

    /**
     * @inheritDoc
     */
    public function getOpenPrice(): string
    {
        return $this->openPrice;
    }

    /**
     * @inheritDoc
     */
    public function getClosePrice(): string
    {
        return $this->closePrice;
    }

    /**
     * @inheritDoc
     */
    public function getLowestPrice(): string
    {
        return $this->lowestPrice;
    }

    /**
     * @inheritDoc
     */
    public function getHighestPrice(): string
    {
        return $this->highestPrice;
    }
}
