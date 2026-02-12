<?php

namespace App\Repository;

use App\Entity\Collaborator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Collaborator>
 */
class CollaboratorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collaborator::class);
    }

    /**
     * @return Collaborator[]
     */
    public function findVisibleForUser(?int $userId): array
    {
        $qb = $this->createQueryBuilder('c');

        if (!$userId) {
            return $qb->orderBy('c.companyName', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return $qb->where('c.isPublic = :true')
            ->orWhere('c.addedBy = :userId')
            ->setParameter('true', true)
            ->setParameter('userId', $userId)
            ->orderBy('c.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Collaborator[]
     */
    public function findBySearchQuery(string $query, ?int $userId): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->where(
            $qb->expr()->orX(
                $qb->expr()->like('c.name', ':query'),
                $qb->expr()->like('c.companyName', ':query'),
                $qb->expr()->like('c.email', ':query'),
                $qb->expr()->like('c.domain', ':query')
            )
        );

        if ($userId) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('c.isPublic', ':true'),
                    $qb->expr()->eq('c.addedBy', ':userId')
                )
            )
                ->setParameter('userId', $userId);
        }

        return $qb->setParameter('query', '%' . $query . '%')
            ->setParameter('true', true)
            ->orderBy('c.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
