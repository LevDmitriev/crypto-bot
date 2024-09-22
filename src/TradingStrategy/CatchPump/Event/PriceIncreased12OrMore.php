<?php

namespace App\TradingStrategy\CatchPump\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Цена за 2 часа с момента открытия увелчиилась на 12 и более процентов
 */
class PriceIncreased12OrMore extends Event
{
}
