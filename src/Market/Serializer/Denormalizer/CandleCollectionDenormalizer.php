<?php

declare(strict_types=1);

namespace App\Market\Serializer\Denormalizer;

use App\Market\Model\Candle;
use App\Market\Model\CandleCollection;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Денормализатор свечей
 */
class CandleCollectionDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    /**
     * @inheritDoc
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $collection = new CandleCollection();
        foreach ($data as $kline) {
            return $collection->add($this->denormalize($kline, Candle::class));
        }

        return $collection;
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
        return $type == CandleCollection::class;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedTypes(?string $format): array
    {
        return [CandleCollection::class => true];
    }
}
