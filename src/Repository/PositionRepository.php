<?php

namespace App\Repository;

use App\Entity\Coin;
use App\Entity\Position;
use App\Entity\Position\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
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
            ->andWhere('p.status <> :status')
            ->setParameter('status', Status::Closed)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Найти все незакрытые позиции
     * @return Collection<int, Position>
     */
    public function findAllNotClosed(): Collection
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->neq('status', Status::Closed->value));
        return $this->matching($criteria);
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
            ->andWhere('p.coin = :coin')
            ->andWhere('p.status <> :status')
            ->setParameter('coin', $coin)
            ->setParameter('status', Status::Closed->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Найти все позиции
     * @param Coin $coin Монета
     *
     * @return array
     */
    public function findAllByCoin(Coin $coin): array
    {
        return parent::findBy(['coin' => $coin->getId()]);
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
