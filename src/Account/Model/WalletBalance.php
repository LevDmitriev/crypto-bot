<?php

namespace App\Account\Model;

/**
 * Балланс кошелька
 */
class WalletBalance
{
    /**
     * @param string $totalWalletBallance Полный баланс кошелька с учётом стоимости всех активов
     * @param string $totalAvailableBalance Доступный баланс
     */
    public function __construct(public readonly string $totalWalletBallance, public readonly string $totalAvailableBalance)
    {
    }
}
