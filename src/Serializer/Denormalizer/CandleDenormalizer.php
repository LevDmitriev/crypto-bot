<?php

declare(strict_types=1);

namespace App\Serializer\Denormalizer;

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
            startTime:  (int) $data[0],
            endTime:    (int) $data[0] + 3600 * 1000,
            openPrice: $data[1],
            highestPrice: $data[2],
            lowestPrice: $data[3],
            closePrice: $data[4],
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
