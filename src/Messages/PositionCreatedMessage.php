<?php

declare(strict_types=1);

namespace App\Messages;

/**
 * Сообщение с информацией по позиции
 */
class PositionCreatedMessage
{
    public function __construct(public int $id)
    {
    }
}
