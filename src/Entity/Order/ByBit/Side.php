<?php

declare(strict_types=1);

namespace App\Entity\Order\ByBit;

enum Side: string
{
    case Buy = 'Buy';
    case Sell = 'Sell';
}
