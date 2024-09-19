<?php

namespace App\Market\Repository;

use App\Entity\Order\ByBit\Category;
use App\Market\Model\CandleCollection;
use ByBit\SDK\Api\MarketApi;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CandleRepository implements CandleRepositoryInterface
{
    public function __construct(private MarketApi $marketApi, private DenormalizerInterface $denormalizer)
    {
    }

    /**
     * Получить коллекцию свечей
     * @link https://bybit-exchange.github.io/docs/v5/market/kline
     * @param string $symbol
     * @param string $interval
     * @param string $category
     * @param int|null $start
     * @param int|null $end
     * @param int $limit
     * @return CandleCollection
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function find(string $symbol, string $interval, string $category = Category::spot->value, int $start = null, int $end = null, int $limit = 200): CandleCollection
    {
        $klines = $this->marketApi->getKline([
            'category' => $category,
            'symbol' => $symbol,
            'interval' => $interval,
            'start' => $start,
            'end' => $end,
            'limit' => $limit,
        ]);
        /** @var CandleCollection $collection */
        $collection = $this->denormalizer->denormalize($klines['list'], CandleCollection::class);
        return $collection;
    }
}
