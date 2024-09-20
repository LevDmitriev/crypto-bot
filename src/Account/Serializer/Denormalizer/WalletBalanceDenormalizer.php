<?php

namespace App\Account\Serializer\Denormalizer;

use App\Account\Model\WalletBalance;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class WalletBalanceDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritDoc
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return new WalletBalance(
            totalWalletBallance: $data['totalWalletBallance'],
            totalAvailableBalance: $data['totalAvailableBalance']
        )
        ;
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === WalletBalance::class;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedTypes(?string $format): array
    {
        return [WalletBalance::class => true];
    }
}
