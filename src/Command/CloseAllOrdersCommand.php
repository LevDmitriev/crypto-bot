<?php

declare(strict_types=1);

namespace App\Command;

use ByBit\SDK\ByBitApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Очистить все заявки
 */
class CloseAllOrdersCommand extends Command
{
    public function __construct(private readonly ByBitApi $byBitApi)
    {
        parent::__construct('app:orders:cancel-all');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->byBitApi->tradeApi()->cancelAllOrders([
                                                   "category" => "linear",
                                                   "symbol" => null,
                                                   "settleCoin" => "USDT",
                                               ]);
        return self::SUCCESS;
    }
}
