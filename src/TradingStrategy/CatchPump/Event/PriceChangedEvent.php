<?php

namespace App\TradingStrategy\CatchPump\Event;

use App\Entity\Position;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Событие изменение цены
 */
class PriceChangedEvent extends Event
{
    public const NAME = 'trading_strategy.catch_pump.price_changed';

    public function __construct(public readonly Position $position, public readonly float $changePercent)
    {
    }

}
