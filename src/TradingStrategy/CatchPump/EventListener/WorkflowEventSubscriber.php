<?php

namespace App\TradingStrategy\CatchPump\EventListener;

use App\Entity\Order\ByBit\Side;
use App\Entity\Position;
use App\Factory\OrderFactory;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Workflow\Event\TransitionEvent;

/**
 * Подписчик на события бизнес-процесса
 */
class WorkflowEventSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderFactory $orderFactory,
    ) { }

    public static function getSubscribedEvents(): array
    {
        return [
            TransitionEvent::getName('catch_pump_position', 'increase_2') => 'moveStopPlusPoint2',
            TransitionEvent::getName('catch_pump_position', 'increase_8') => 'sell50Percent',
            TransitionEvent::getName('catch_pump_position', 'increase_12') => 'sell25Percent',
            TransitionEvent::getName('catch_pump_position', 'increase_13') => 'moveStopPlus10point2',
        ];
    }

    /**
     * Увеличить триггерную цену стопа на 0,2% от изначальной
     *
     * @param TransitionEvent $event
     *
     * @return void
     */
    public function moveStopPlusPoint2(TransitionEvent $event): void
    {
        $position = $event->getSubject();
        assert($position instanceof Position);
        $this->logger?->info("Увеличиваем стоп на 2% позиции {$position->getId()}}");
        $orders = $position->getOrdersCollection();
        $buyOrder = $orders->filterBuyOrders()->first();
        $stopOrder = $orders->filterStopOrders()->first();
        $triggerPrice = bcmul($buyOrder->getAveragePrice(), '1.002', 6);
        $stopOrder->setTriggerPrice($triggerPrice);
        $this->entityManager->persist($stopOrder);
    }

    /**
     * Выставить приказ на продажу 50% позиции
     *
     * @param TransitionEvent $event
     *
     * @return void
     */
    public function sell50Percent(TransitionEvent $event): void
    {
        $position = $event->getSubject();
        assert($position instanceof Position);
        $this->logger?->info("Продажа 50% позиции {$position->getId()}}");
        $orders = $position->getOrdersCollection();
        $buyOrder = $orders->filterBuyOrders()->first();
        $quantityForSale = bcdiv($buyOrder->getRealExecutedQuantity(), '2', 6);
        $stopOrder = $orders->filterStopOrders()->first();
        $stopOrder->setTriggerPrice(bcmul($buyOrder->getAveragePrice(), '1.02', 6));
        $stopOrder->setQuantity(bcsub($stopOrder->getQuantity(), $quantityForSale, 6));
        // Сначала обновляем стоп-приказ, а потом добавляем новый. Порядок важен.
        $this->entityManager->persist($stopOrder);
        $this->entityManager->flush();
        $order = $this->orderFactory->create(coin: $position->getCoin(), quantity: $quantityForSale, side: Side::Sell);
        $order->setPosition($position);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }
    /**
     * Выставить приказ на продажу 25% позиции
     *
     * @param TransitionEvent $event
     *
     * @return void
     */
    public function sell25Percent(TransitionEvent $event): void
    {
        $position = $event->getSubject();
        assert($position instanceof Position);
        $this->entityManager->beginTransaction();
        $this->logger?->info("Продажа 25% позиции {$position->getId()}}");
        $orders = $position->getOrdersCollection();
        $buyOrder = $orders->filterBuyOrders()->first();
        $quantityForSale = bcdiv($buyOrder->getRealExecutedQuantity(), '4', 6);
        $stopOrder = $orders->filterStopOrders()->first();
        $stopOrder->setTriggerPrice(bcmul($buyOrder->getAveragePrice(), '1.082', 6));
        $stopOrder->setQuantity(bcsub($stopOrder->getQuantity(), $quantityForSale, 6));
        // Сначала обновляем стоп-приказ, а потом добавляем новый. Порядок важен.
        $this->entityManager->persist($stopOrder);
        $this->entityManager->flush();
        $order = $this->orderFactory->create(coin: $position->getCoin(), quantity: $quantityForSale, side: Side::Sell);
        $order->setPosition($position);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
        $this->entityManager->commit();
    }

    /**
     * Увеличить триггерную цену стопа на 10,2% от изначальной
     *
     * @param TransitionEvent $event
     *
     * @return void
     */
    public function moveStopPlus10point2(TransitionEvent $event): void
    {
        $position = $event->getSubject();
        assert($position instanceof Position);
        /** @var Lock $lock */
        $this->entityManager->beginTransaction();
        $this->logger?->info("Увеличиваем стоп на 10.2% позиции {$position->getId()}}");
        $orders = $position->getOrdersCollection();
        $buyOrder = $orders->filterBuyOrders()->first();
        $stopOrder = $orders->filterStopOrders()->first();
        $triggerPrice = bcmul($buyOrder->getAveragePrice(), '1.102', 6);
        $stopOrder->setTriggerPrice($triggerPrice);
        $this->entityManager->persist($stopOrder);
        $this->entityManager->flush();
        $this->entityManager->commit();
    }
}
