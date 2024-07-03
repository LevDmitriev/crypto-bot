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
        $coin = $this->coinRepository->findOneBy(['code' => 'BTC']);
        $order = new Order();
        $order->setCoin($coin);
        $order->setQuantity(1);
        $order->setSide(Order\ByBit\Side::Buy);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return self::SUCCESS;
    }
}
