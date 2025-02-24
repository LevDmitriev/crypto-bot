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
     * Получить коллекцию свечей по фильтрам
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
        $parameters = [
            'category' => $category,
            'symbol' => $symbol,
            'interval' => $interval,
            'limit' => $limit,
        ];
        if ($start){
            $parameters['start'] = $start;
        }
        if ($end){
            $parameters['end'] = $end;
        }
        $klines = $this->marketApi->getKline($parameters);
        /** @var CandleCollection $collection */
        $collection = $this->denormalizer->denormalize($klines['list'], CandleCollection::class);
        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function getLastTradedPrice(string $symbol, string $category = Category::spot->value): string
    {
        $klines = $this->marketApi->getKline([
            'category' => $category,
            'symbol' => $symbol,
            'interval' => 1,
            'limit' => 1,
        ]);

        return $klines['list'][0][4];
    }
}
