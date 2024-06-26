<?php

declare(strict_types=1);

namespace App\Messages;

/**
 * Комманда с ID приказа на проставление статуса
 */
class OrderSetStatusMessage
{
    public function __construct(public string $id)
    {
    }
}
