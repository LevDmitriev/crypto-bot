<?php

namespace App\Market\Repository;

use App\Entity\Order\ByBit\Category;
use App\Market\Model\CandleCollection;

/**
 * Интерфейс репозитория свечей
 */
interface CandleRepositoryInterface
{
    public function find(string $symbol, string $interval, string $category = Category::spot->value, int $start = null, int $end = null, int $limit = 200): CandleCollection;
}
