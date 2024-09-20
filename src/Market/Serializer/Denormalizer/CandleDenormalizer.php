<?php

declare(strict_types=1);

namespace App\Market\Serializer\Denormalizer;

use App\Market\Model\Candle;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Денормализатор свечей
 */
class CandleDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritDoc
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return new Candle(
            startTime: (int) $data[0],
            openPrice: $data[1],
            closePrice: $data[4],
            highestPrice: $data[2],
            lowestPrice: $data[3],
            volume: $data[5],
            turnover: $data[6],
        );
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return $type == Candle::class;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedTypes(?string $format): array
    {
        return [Candle::class => true];
    }
}
