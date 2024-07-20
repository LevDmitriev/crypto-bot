<?php

namespace App\TradingStrategy;

/**
 * Интерфейс свечи
 */
interface CandleInterface
{
    /**
     * Получить дату начала
     * @return int
     */
    public function getStartTime(): int;

    /**
     * Получить дату окончания
     * @return int
     */
    public function getEndTime(): int;

    /**
     * Получить цену открытия
     * @return string
     */
    public function getOpenPrice(): string;

    /**
     * Получить цену закрытия
     * @return string
     */
    public function getClosePrice(): string;

    /**
     * Получить самую низкую цену
     * @return string
     */
    public function getLowestPrice(): string;

    /**
     * Получить самую высокую цену
     * @return string
     */
    public function getHighestPrice(): string;

    /**
     * Получить объём торгов
     * @return string
     */
    public function getVolume(): string;
}
