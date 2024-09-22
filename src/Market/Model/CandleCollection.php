<?php

namespace App\Market\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Коллекция свечей
 * @extends ArrayCollection<int, Candle>
 */
class CandleCollection extends ArrayCollection implements CandleInterface
{
    public function getHighestPrice(): string
    {
        assert($this->count());
        $result = '0';
        foreach ($this as $candle) {
            $result = \bccomp($candle->getHighestPrice(), $result, 4) === 1 ? $candle->getHighestPrice() : $result;
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
        return $this->last()->getClosePrice();
    }

    public function getLowestPrice(): string
    {
        assert($this->count());
        $result = '0';
        foreach ($this as $candle) {
            $result = \bccomp($candle->getLowestPrice(), $result, 4) === 1 ? $candle->getLowestPrice() : $result;
        }
        return $result;
    }

    public function getVolume(): string
    {
        $result = '0';
        foreach ($this as $candle) {
            $result = \bcadd($result, $candle->getVolume(), 2);
        }

        return $result;
    }
}
