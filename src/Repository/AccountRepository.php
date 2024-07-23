<?php

namespace App\Repository;

use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;

class AccountRepository
{
    public function __construct(private ByBitApi $byBitApi)
    {
    }


    /**
     * Получить свободный баланс USDT
     * @return string
     */
    public function getDeposit(): string
    {
        $result = $this->byBitApi->accountApi()->getWalletBalance(['accountType' => 'SPOT', 'coin' => 'USDT']);
        return $result['list'][0]['coin'][0]['free'];
    }
}
