<?php

declare(strict_types=1);

namespace App\TradingStrategy;

use App\Entity\Coin;
use App\Repository\CoinRepository;
use App\Repository\PositionRepository;
use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Фабрика торговых стратегий
 */
class TradingStrategyFactory implements TradingStrategyFactoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PositionRepository $positionRepository,
        //        #[Autowire(service: 'app.serializer.bybit')]
        private readonly DenormalizerInterface $denormalizer,
        private readonly ByBitApi $byBitApi
    ) {
    }

    public function create(string $name, Coin $coin): TradingStrategyInterface
    {
        return match ($name) {
            "catch-pump" => new CatchPumpStrategy(
                $coin,
                $this->entityManager,
                $this->positionRepository,
                $this->denormalizer,
                $this->byBitApi
            )
        };
    }
}
