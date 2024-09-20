<?php

namespace App\Market\Repository;

use App\Entity\Order\ByBit\Category;
use App\Market\Model\CandleCollection;

/**
 * Интерфейс репозитория свечей
 */
interface CandleRepositoryInterface
{
    /**
     * Получить коллекцию свечей по условию
     * @param string $symbol
     * @param string $interval
     * @param string $category
     * @param int|null $start
     * @param int|null $end
     * @param int $limit
     * @return CandleCollection
     */
    public function find(string $symbol, string $interval, string $category = Category::spot->value, int $start = null, int $end = null, int $limit = 200): CandleCollection;

    /**
     * Получить последнюю цену символа
     * @param string $symbol символ
     * @return string
     */
    public function getLastTradedPrice(string $symbol, string $category = Category::spot->value): string;
}
