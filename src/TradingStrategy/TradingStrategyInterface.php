<?php

namespace App\TradingStrategy;

use App\Entity\Position;

/**
 * Интерфейс торговой стратегии
 */
interface TradingStrategyInterface
{
    /**
     * Подождать до момента, когда следует открывать позицию и открываем её
     * @return Position
     */
    public function waitAndOpenPosition(): Position;

    /**
     * Подождать до момента, когда следует закрывать позицию и закрываем её
     * @return Position
     */
    public function waitAndClosePosition(): Position;

    /**
     * Есть ли уже открытая позиция?
     * @return bool
     */
    public function hasOpenedPosition(): bool;
}
