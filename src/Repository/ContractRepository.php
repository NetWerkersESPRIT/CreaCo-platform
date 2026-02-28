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

    /**
     * @return Contract[]
     */
    public function filterContracts(int $userId, string $role, ?string $status = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.collaborator', 'collab')
            ->leftJoin('c.collabRequest', 'r');

        if ($role === 'ROLE_MANAGER') {
            $qb->andWhere('r.revisor = :userId');
        } else {
            $qb->andWhere('c.creator = :userId');
        }
        $qb->setParameter('userId', $userId);

        if ($status && $status !== 'ALL') {
            $qb->andWhere('c.status = :status')
                ->setParameter('status', $status);
        }

        if ($search) {
            $qb->andWhere('c.title LIKE :search OR collab.companyName LIKE :search OR c.contractNumber LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveForRevisor(int $revisorId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('count(c.id)')
            ->innerJoin('c.collabRequest', 'r')
            ->andWhere('r.revisor = :revisorId')
            ->andWhere('c.status = :status')
            ->setParameter('revisorId', $revisorId)
            ->setParameter('status', 'ACTIVE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalBudgetForRevisor(int $revisorId): float
    {
        return (float) $this->createQueryBuilder('c')
            ->select('SUM(c.amount)')
            ->innerJoin('c.collabRequest', 'r')
            ->andWhere('r.revisor = :revisorId')
            ->setParameter('revisorId', $revisorId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
