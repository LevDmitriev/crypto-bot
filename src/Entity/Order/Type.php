<?php

declare(strict_types=1);

namespace App\Entity\Order;

enum Type: string
{
    case Market = 'Market';
    case Diamonds = 'Limit';
}
