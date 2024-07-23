<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Order;
use App\Repository\CoinRepository;
use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CoinRepository $coinRepository,
        private readonly ByBitApi $byBitApi
    ) {
        parent::__construct('test:command');
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->byBitApi->marketApi()->getInstrumentsInfo(['category'=> 'spot', 'symbol'=>'BTCUSDT']);
        echo print_r($result, true);

        return self::SUCCESS;
    }
}
