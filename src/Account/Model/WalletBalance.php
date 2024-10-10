<?php

namespace App\Account\Model;

/**
 * Балланс кошелька
 */
readonly class WalletBalance
{
    /**
     * @param string $totalWalletBalance    Полный баланс кошелька с учётом стоимости всех активов
     * @param string $totalAvailableBalance Доступный баланс
     * @param string $totalEquity Суммарная стоимость всех активов
     */
    public function __construct(
        public string $totalWalletBalance,
        public string $totalAvailableBalance,
        public string $totalEquity
    ) {
    }
}
