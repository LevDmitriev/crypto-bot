<?php

declare(strict_types=1);

namespace App\Entity\Order\ByBit;

/**
 * @link https://bybit-exchange.github.io/docs/v5/enum#orderstatus
 */
enum Status: string
{
    case New = 'New';
    case PartiallyFilled = 'PartiallyFilled';
    case Untriggered = 'Untriggered';
    case Rejected = 'Rejected';
    case PartiallyFilledCanceled = 'PartiallyFilledCanceled';
    case Filled = 'Filled';
    case Cancelled = 'Cancelled';
    case Triggered = 'Triggered';
    case Deactivated = 'Deactivated';
}
