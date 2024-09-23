<?php

declare(strict_types=1);

namespace App\Messages;

/**
 * Команда на обновление приказа данными из ByBit.
 * Обычные рыночные приказы выполняются быстро и можжем позволить немного подождать, но сделать процесс синхронным.
 */
class EnrichMarketOrderFromByBitCommand
{
    public function __construct(public string $id)
    {
    }
}
