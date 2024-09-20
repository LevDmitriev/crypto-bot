<?php

namespace App\TradingStrategy;

use App\Entity\Position;

/**
 * Интерфейс торговой стратегии
 */
interface TradingStrategyInterface
{
    /**
     * Открыть позицию
     * @return Position
     */
    public function openPosition(): Position;

    /**
     * Можно открывать позицию
     * @return bool
     */
    public function canOpenPosition(): bool;

    /**
     * Закрыть позицию
     * @return Position
     */
    public function сlosePosition(): Position;

    /**
     * Есть ли уже открытая позиция?
     * @return bool
     */
    public function hasOpenedPosition(): bool;
}
