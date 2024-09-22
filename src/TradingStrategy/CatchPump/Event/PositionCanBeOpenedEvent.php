<?php

namespace App\TradingStrategy\CatchPump\Event;

use Symfony\Contracts\EventDispatcher\Event;

class PositionCanBeOpenedEvent extends Event
{
    public const NAME = 'trading_strategy.catch_pump.position_can_be_opened';
}
