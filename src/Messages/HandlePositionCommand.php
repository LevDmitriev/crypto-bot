<?php

namespace App\Messages;

/**
 * Команда обработки позиции торговой стратегией
 */
readonly class HandlePositionCommand
{
    public function __construct(public string $positionId)
    {
    }
}
