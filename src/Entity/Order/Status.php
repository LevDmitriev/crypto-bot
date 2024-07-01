<?php

declare(strict_types=1);

namespace App\Entity\Order;

/**
 * Статусы приказов
 */
enum Status: string
{
    case New = 'New';
    case Filled = 'Filled';
    case Cancelling = 'OnCancelling';
    case Cancelled = 'Cancelled';
}
