<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\TradingStrategy\Candle;
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
            startTime:  (int) $data['start'],
            endTime:    (int) $data['end'],
            openPrice: $data['open'],
            closePrice: $data['close'],
            highestPrice: $data['high'],
            lowestPrice: $data['low'],
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
        return $type == Candle::class
               && is_array($data)
               && isset($data['start'])
               && isset($data['end'])
               && isset($data['open'])
               && isset($data['close'])
               && isset($data['high'])
               && isset($data['low'])
        ;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedTypes(?string $format): array
    {
        return [Candle::class => true];
    }
}
