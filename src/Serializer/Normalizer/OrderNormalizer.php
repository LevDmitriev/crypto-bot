<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\Entity\Order;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Нормализатор приказов
 */
class OrderNormalizer implements NormalizerInterface
{
    /**
     * @inheritDoc
     */
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
        assert($object instanceof Order);

        return [
            'orderLinkId' => $object->getId(),
            'category' => $object->getCategory()->value,
            'symbol' => $object->getSymbol(),
            'side' => $object->getSide()->value,
            'orderType' => $object->getType()->value,
            'qty' => (string) $object->getQuantity(),
            'price' => (string) $object->getPrice(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Order;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedTypes(?string $format): array
    {
        return [Order::class => true];
    }
}
