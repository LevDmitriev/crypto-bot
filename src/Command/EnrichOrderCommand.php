<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Order;
use App\Messages\EnrichOrderFromByBitCommand;
use App\Repository\CoinRepository;
use App\Repository\OrderRepository;
use ByBit\SDK\ByBitApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

/**
 * Обогатить приказы данными из ByBit
 */
class EnrichOrderCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct('app:enrich-order');
    }

    protected function configure()
    {
        $this->addArgument('ordersIds', InputArgument::IS_ARRAY, 'ID Приказов');
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 'ID Приказа');
        $this->setDescription('Обогатить приказ данными из ByBit');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ordersIds = $input->getArgument('ordersIds');
        if ($input->getOption('all')) {
            $ordersIds = $this->orderRepository->createQueryBuilder('o')->select('o.id')->getQuery()->getScalarResult();
            $ordersIds = array_column($ordersIds, 'id');
        }
        foreach ($ordersIds as $orderId) {
            $this->commandBus->dispatch(
                new EnrichOrderFromByBitCommand($orderId),
                [new TransportMessageIdStamp("enrich_order_$orderId")]
            );
            $this->entityManager->clear();
        }
        return self::SUCCESS;
    }
}
