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
        return $this->reduce(
            fn (string $accumulator, Candle $candle) => \bccomp($candle->getHighestPrice(), $accumulator, 4) === 1 ? $candle->getHighestPrice() : $accumulator,
            '0'
        );
    }
    public function add(mixed $element): void
    {
        assert($element instanceof CandleInterface);
        parent::add($element);
    }

    /**
     * Получить время начала свечи
     * @return int
     */
    public function getStartTime(): int
    {
        assert(!$this->isEmpty());
        return $this->reduce(
            fn (string $accumulator, Candle $candle) => \bccomp($candle->getStartTime(), $accumulator, 4) === -1 ? $candle->getStartTime() : $accumulator,
            (string) PHP_INT_MAX
        );
    }

    /**
     * @@inheritDoc
     */
    public function getOpenPrice(): string
    {
        assert(!$this->isEmpty());
        $first = $this->reduce(fn (?Candle $accumulator, Candle $current) => $accumulator && ($accumulator->getStartTime() < $current->getStartTime()) ? $accumulator : $current, null);
        return $first->getOpenPrice();
    }

    /**
     * @inheritDoc
     */
    public function getClosePrice(): string
    {
        assert(!$this->isEmpty());
        $last = $this->reduce(fn (?Candle $accumulator, Candle $current) => $accumulator && ($accumulator->getStartTime() > $current->getStartTime()) ? $accumulator : $current, null);
        return $last->getClosePrice();
    }

    public function getLowestPrice(): string
    {
        assert(!$this->isEmpty());
        return $this->reduce(
            fn (string $accumulator, Candle $candle) => \bccomp($candle->getLowestPrice(), $accumulator, 4) === -1 ? $candle->getLowestPrice() : $accumulator,
            (string) PHP_INT_MAX
        );
    }

    public function getVolume(): string
    {
        assert(!$this->isEmpty());
        return $this->reduce(fn (string $accumulator, Candle $candle) => \bcadd($accumulator, $candle->getVolume(), 2), '0');
    }
}
