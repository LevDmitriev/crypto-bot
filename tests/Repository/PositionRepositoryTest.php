<?php

namespace App\Tests\Repository;

use App\Entity\Coin;
use App\Entity\Position;
use App\Entity\Position\Status;
use App\Repository\PositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @coversDefaultClass \App\Repository\PositionRepository
 */
class PositionRepositoryTest extends KernelTestCase
{
    /**
     * @covers ::findAllNotClosedByCoin
     * @return void
     */
    public function testFindAllNotClosedByCoin(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        /** @var PositionRepository $positionRepository */
        $positionRepository = self::getContainer()->get(PositionRepository::class);
        foreach ($positionRepository->findAll() as $position) {
            $entityManager->remove($position);
        }
        $entityManager->flush();
        $coin = new Coin();
        $coin->setByBitCode(uniqid());
        $entityManager->persist($coin);
        $entityManager->flush();
        $count = 2;
        for ($i = 0; $i < $count; $i++) {
            $position = new Position();
            $position->setCoin($coin);
            $position->setStrategyName(uniqid());
            $entityManager->persist($position);
        }
        $closedPosition = new Position();
        $closedPosition->setCoin($coin);
        $closedPosition->setStrategyName(uniqid());
        $closedPosition->setStatus(Status::Closed->value);
        $positionWithDifferentCoin = new Position();
        $positionWithDifferentCoin->setCoin((new Coin())->setByBitCode(uniqid()));
        $positionWithDifferentCoin->setStrategyName(uniqid());
        $entityManager->persist($positionWithDifferentCoin);
        $entityManager->flush();
        $entityManager->persist($closedPosition);
        $entityManager->flush();
        $result = $positionRepository->findAllNotClosedByCoin($coin);
        self::assertCount($count, $result);
        self::assertCount($count, $result->filter(fn(Position $position) => $position->getStatus() !== Status::Closed->value));
    }

    /**
     * @covers ::getTotalNotClosedCount
     * @return void
     */
    public function testGetTotalNotClosedCount(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        /** @var PositionRepository $positionRepository */
        $positionRepository = self::getContainer()->get(PositionRepository::class);
        foreach ($positionRepository->findAll() as $position) {
            $entityManager->remove($position);
        }
        $entityManager->flush();
        $coin = new Coin();
        $coin->setByBitCode(uniqid());
        $entityManager->persist($coin);
        $entityManager->flush();
        $count = 2;
        for ($i = 0; $i < $count; $i++) {
            $position = new Position();
            $position->setCoin($coin);
            $position->setStrategyName(uniqid());
            $entityManager->persist($position);
        }
        $closedPosition = new Position();
        $closedPosition->setCoin($coin);
        $closedPosition->setStrategyName(uniqid());
        $closedPosition->setStatus(Status::Closed->value);
        $entityManager->persist($closedPosition);
        $entityManager->flush();
        $result = $positionRepository->getTotalNotClosedCount();
        self::assertEquals($count, $result);
    }

    /**
     * @covers ::findAllNotClosed
     * @return void
     */
    public function testFindAllNotClosed(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        /** @var PositionRepository $positionRepository */
        $positionRepository = self::getContainer()->get(PositionRepository::class);
        foreach ($positionRepository->findAll() as $position) {
            $entityManager->remove($position);
        }
        $entityManager->flush();
        $coin = new Coin();
        $coin->setByBitCode(uniqid());
        $entityManager->persist($coin);
        $entityManager->flush();
        for ($i = 0; $i < 2; $i++) {
            $position = new Position();
            $position->setCoin($coin);
            $position->setStrategyName(uniqid());
            $entityManager->persist($position);
        }
        $closedPosition = new Position();
        $closedPosition->setCoin($coin);
        $closedPosition->setStrategyName(uniqid());
        $closedPosition->setStatus(Status::Closed->value);
        $entityManager->persist($closedPosition);
        $positionWithDifferentCoin = new Position();
        $positionWithDifferentCoin->setCoin((new Coin())->setByBitCode(uniqid()));
        $positionWithDifferentCoin->setStrategyName(uniqid());
        $entityManager->persist($positionWithDifferentCoin);
        $entityManager->flush();
        $result = $positionRepository->findAllNotClosed();
        self::assertCount(3, $result);
        self::assertCount(3, $result->filter(fn(Position $position) => $position->getStatus() !== Status::Closed->value));
    }

    /**
     * @covers ::findOneNotClosedByCoin
     * @return void
     */
    public function testFindOneNotClosedByCoin(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        /** @var PositionRepository $positionRepository */
        $positionRepository = self::getContainer()->get(PositionRepository::class);
        foreach ($positionRepository->findAll() as $position) {
            $entityManager->remove($position);
        }
        $entityManager->flush();
        $coin = new Coin();
        $coin->setByBitCode(uniqid());
        $entityManager->persist($coin);
        $entityManager->flush();
        $count = 2;
        for ($i = 0; $i < $count; $i++) {
            $position = new Position();
            $position->setCoin($coin);
            $position->setStrategyName(uniqid());
            $entityManager->persist($position);
        }
        $closedPosition = new Position();
        $closedPosition->setCoin($coin);
        $closedPosition->setStrategyName(uniqid());
        $closedPosition->setStatus(Status::Closed->value);
        $positionWithDifferentCoin = new Position();
        $positionWithDifferentCoin->setCoin((new Coin())->setByBitCode(uniqid()));
        $positionWithDifferentCoin->setStrategyName(uniqid());
        $entityManager->persist($positionWithDifferentCoin);
        $entityManager->persist($closedPosition);
        $entityManager->flush();
        $result = $positionRepository->findOneNotClosedByCoin($coin);
        self::assertNotNull($result);
        self::assertEquals($coin->getId(), $result->getCoin()->getId());
    }
}
