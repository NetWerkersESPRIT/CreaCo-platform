<?php

namespace App\Repository;

use App\Entity\CollabRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CollabRequest>
 */
class CollabRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollabRequest::class);
    }

    /**
     * @return CollabRequest[]
     */
    public function findByCreator(int $creatorId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.creator = :creatorId')
            ->setParameter('creatorId', $creatorId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CollabRequest[]
     */
    public function findPendingByRevisor(int $revisorId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.revisor = :revisorId')
            ->andWhere('r.status = :status')
            ->setParameter('revisorId', $revisorId)
            ->setParameter('status', 'PENDING')
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CollabRequest[]
     */
    public function findByRevisor(int $revisorId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.revisor = :revisorId')
            ->setParameter('revisorId', $revisorId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countPendingByRevisor(int $revisorId): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('count(r.id)')
            ->andWhere('r.revisor = :revisorId')
            ->andWhere('r.status = :status')
            ->setParameter('revisorId', $revisorId)
            ->setParameter('status', 'PENDING')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
