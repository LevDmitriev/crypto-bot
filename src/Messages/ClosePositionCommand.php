<?php

namespace App\Messages;

/**
 * Закрыть позицию
 */
class ClosePositionCommand
{
    public function __construct(public readonly string $positionId) { }
}
