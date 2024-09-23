<?php

declare(strict_types=1);

namespace App\Entity\Order\ByBit;

enum OrderFilter: string
{
    case Order = 'Order';
    case tpslOrder = 'tpslOrder';
    case StopOrder = 'StopOrder';
}
