<?php

namespace App\TradingStrategy\CatchPump\Event;

use App\Entity\Position;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Цена за 2 часа с момента открытия увелчиилась на 8 и более процентов
 */
class PriceIncreased8OrMore extends Event
{
    public function __construct(public readonly Position $position)
    {
    }

    public const NAME = 'trading_strategy.catch_pump.price_increased_8_or_more';
}
