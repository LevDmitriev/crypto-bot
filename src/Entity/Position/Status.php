<?php

declare(strict_types=1);

namespace App\Entity\Position;

/**
 * @link https://bybit-exchange.github.io/docs/v5/enum#orderstatus
 */
enum Status: string
{
    case New = 'New';
    case BuyOrderFullFilled = 'BuyOrderFullFilled';
    case Closed = 'Closed';
}
