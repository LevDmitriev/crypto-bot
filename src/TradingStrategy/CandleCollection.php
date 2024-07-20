<?php

namespace App\TradingStrategy;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Коллекция свечей
 * @extends ArrayCollection<int, Candle>
 */
class CandleCollection extends ArrayCollection implements CandleInterface
{
    public function getHighestPrice(): string
    {
        $result = '0';
        foreach ($this as $candle) {
            $result = bccomp($candle->getHighestPrice(), $result) === 1 ? $candle->getHighestPrice() : $result;
        }
        return $result;
    }

    public function getStartTime(): int
    {
        // TODO: Implement getStartTime() method.
    }

    public function getEndTime(): int
    {
        // TODO: Implement getEndTime() method.
    }

    public function getOpenPrice(): string
    {
        // TODO: Implement getOpenPrice() method.
    }

    public function getClosePrice(): string
    {
        // TODO: Implement getClosePrice() method.
    }

    public function getLowestPrice(): string
    {
        // TODO: Implement getLowestPrice() method.
    }

    public function getVolume(): string
    {
        $result = '0';
        foreach ($this as $candle) {
            $result = bcadd($result, $candle->getVolume());
        }

        return $result;
    }
}
