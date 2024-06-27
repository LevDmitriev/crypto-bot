<?php

declare(strict_types=1);

namespace App\Entity\Position;

/**
 * @link https://bybit-exchange.github.io/docs/v5/enum#orderstatus
 */
enum Status: string
{
    case BuyOrderFullFilled = 'BuyOrderFullFilled';
    case SellOrderCreated = 'SellOrderCreated';
    case Closed = 'Closed';
}
