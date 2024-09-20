<?php

namespace App\Repository;

use App\Account\Model\WalletBalance;
use ByBit\SDK\ByBitApi;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class AccountRepository
{
    public function __construct(private ByBitApi $byBitApi, private readonly DenormalizerInterface $denormalizer)
    {
    }

    /**
     * Получить баланс кошелька
     * @return WalletBalance
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function getWalletBalance(): WalletBalance
    {
        $result = $this->byBitApi->accountApi()->getWalletBalance(['accountType' => 'UNIFIED']);
        return $this->denormalizer->denormalize($result['list'], WalletBalance::class);
    }
}
