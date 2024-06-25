<?php

namespace App\TradingStrategy;

/**
 * Интерфейс торговой стратегии
 */
interface TradingStrategyInterface
{
    /**
     * Обработать свечу
     * @param CandleInterface $candle
     *
     * @return void
     */
    public function handleCandle(CandleInterface $candle): void;
}
