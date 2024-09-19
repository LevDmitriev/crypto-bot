<?php

declare(strict_types=1);

namespace App\Market\Model;

/**
 * Свеча
 */
class Candle implements CandleInterface
{
    public function __construct(
        private readonly int $startTime,
        private readonly string $openPrice,
        private readonly string $closePrice,
        private readonly string $highestPrice,
        private readonly string $lowestPrice,
        private readonly string $volume,
        private readonly string $turnover
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

    public function getVolume(): string
    {
        return $this->volume;
    }

    public function getTurnover(): string
    {
        return $this->turnover;
    }
}
