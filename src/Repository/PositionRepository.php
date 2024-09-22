<?php

namespace App\Repository;

use App\Entity\Coin;
use App\Entity\Position;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Position>
 */
class PositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }

    public function getTotalNotClosedCount(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->where('status != :status')
            ->setParameter('status', Position\Status::Closed)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalCount(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->getQuery()
            ->getSingleScalarResult();
    }


    public function findOneNotClosedByCoin(Coin $coin): ?Position
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.buyOrder', 'buyOrder')
            ->where('buyOrder.coin = :coin')
            ->where('status != :status')
            ->setParameter('coin', $coin)
            ->setParameter('status', Position\Status::Closed)
            ->getQuery()
            ->getOneOrNullResult();
    }
    //    /**
    //     * @return Position[] Returns an array of Position objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Position
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
