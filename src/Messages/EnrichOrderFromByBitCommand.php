<?php

declare(strict_types=1);

namespace App\Messages;

/**
 * Команда на обновление приказа данными из ByBit
 */
class EnrichOrderFromByBitCommand
{
    public function __construct(public string $id)
    {
    }
}
