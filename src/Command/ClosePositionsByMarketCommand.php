<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Order;
use App\Entity\Order\ByBit\Type;
use App\Entity\Position\Status;
use App\Repository\PositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Закрыть позиции по рынку
 */
class ClosePositionsByMarketCommand extends Command
{
    public function __construct(
        private readonly PositionRepository $positionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkflowInterface $orderStateMachine
    ) {
        parent::__construct('app:positions:close-by-market');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $positions = $this->positionRepository->findBy(['status' => Status::SellOrderCreated]);
        foreach ($positions as $position) {
            $sellOrder = $position->getSellOrder();
            if ($sellOrder && $sellOrder->getStatus() === Order\Status::New->value && $sellOrder->getType() == Type::Limit) {
                $newOrder = new Order();
                $newOrder->setType(Type::Market);
                $newOrder->setQuantity($sellOrder->getPrice()); // В рыночной заявке в поле количество нужно писать цену
                $newOrder->setCoin($sellOrder->getCoin());
                $newOrder->setCategory($sellOrder->getCategory());
                $newOrder->setSide($sellOrder->getSide());
                $position->setSellOrder($newOrder);
                $this->orderStateMachine->apply($sellOrder, 'start_cancelling');
                $this->entityManager->persist($position);
            }
        }
        $this->entityManager->flush();

        return self::SUCCESS;
    }
}
