<?php

namespace App\Market\Model;

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
     * Получить цену открытия
     * @return string 6 символов после запятой
     */
    public function getOpenPrice(): string;

    /**
     * Получить цену закрытия
     * @return string 6 символов после запятой
     */
    public function getClosePrice(): string;

    /**
     * Получить самую низкую цену
     * @return string 6 символов после запятой
     */
    public function getLowestPrice(): string;

    /**
     * Получить самую высокую цену
     * @return string 6 символов после запятой
     */
    public function getHighestPrice(): string;

    /**
     * Получить объём торгов
     * @return string
     */
    public function getVolume(): string;
}
