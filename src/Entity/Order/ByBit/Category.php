<?php

declare(strict_types=1);

namespace App\Entity\Order\ByBit;

enum Category: string
{
    case spot = 'spot';
    case linear = 'linear';
    case inverse = 'inverse';
}
