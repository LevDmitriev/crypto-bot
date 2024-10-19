<?php

declare(strict_types=1);

namespace App\Entity\Position;

/**
 * Статусы позиций
 */
enum Status: string
{
    case Opened = 'Opened';
    case Draft = 'Draft';
    case Closed = 'Closed';
}
