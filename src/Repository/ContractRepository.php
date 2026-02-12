<?php

namespace App\Repository;

use App\Entity\Contract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contract>
 */
class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    /**
     * @return Contract[]
     */
    public function findByCreator(int $creatorId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.creator = :creatorId')
            ->setParameter('creatorId', $creatorId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string[] $statuses
     * @return Contract[]
     */
    public function findByCreatorAndStatus(int $creatorId, array $statuses): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.creator = :creatorId')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('creatorId', $creatorId)
            ->setParameter('statuses', $statuses)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByContractNumberAndToken(string $contractNumber, string $token): ?Contract
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.contractNumber = :contractNumber')
            ->andWhere('c.signatureToken = :token')
            ->setParameter('contractNumber', $contractNumber)
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Contract[]
     */
    public function findForRevisor(int $revisorId): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.collabRequest', 'r')
            ->andWhere('r.revisor = :revisorId')
            ->setParameter('revisorId', $revisorId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveByCreator(int $creatorId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('count(c.id)')
            ->andWhere('c.creator = :creatorId')
            ->andWhere('c.status = :status')
            ->setParameter('creatorId', $creatorId)
            ->setParameter('status', 'ACTIVE')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
