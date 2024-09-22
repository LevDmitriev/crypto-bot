<?php

declare(strict_types=1);

namespace App\Messages;

/**
 * Сообщение о том, что создан приказ с ID приказа
 */
class SendOrderToByBitCommand
{
    public function __construct(public string $id)
    {
    }
}
