<?php

namespace App\TradingStrategy;

use App\Entity\Coin;
use App\Entity\Position;

/**
 * Интерфейс торговой стратегии
 */
interface TradingStrategyInterface
{
    /**
     * Открыть позицию, если возможно
     * @return Position|null
     */
    public function openPositionIfPossible(Coin $coin): ?Position;

    /**
     * Обработать позицию
     * @param Position $position
     *
     * @return void
     */
    public function handlePosition(Position $position): void;
}
