<?php

namespace App\TradingStrategy\CatchPump\Event;

use App\Entity\Position;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Цена за 2 часа с момента открытия цена изменилась
 */
class LastTwoHoursPriceChangedEvent extends Event
{
    public const NAME = 'trading_strategy.catch_pump.last_2_hours_price_changed';

    public function __construct(public readonly Position $position, public readonly float $changePercent)
    {
    }

}
