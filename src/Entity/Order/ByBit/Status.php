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

    public static function isOpenStatus(Status $status): bool
    {
        return in_array($status, [self::New, self::PartiallyFilled, self::Untriggered]);
    }
    public static function isClosedStatus(Status $status): bool
    {
        $statuses = [
            self::Rejected,
            self::PartiallyFilledCanceled,
            self::Filled,
            self::Cancelled,
            self::Triggered,
            self::Deactivated,
        ];

        return in_array($status, $statuses);
    }
}
