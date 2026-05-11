<?php

namespace App\Repository;

use App\Entity\HelpTicket;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HelpTicket>
 */
class HelpTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HelpTicket::class);
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('count(h.id)')
            ->where('h.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByPriority(string $priority): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('count(h.id)')
            ->where('h.priority = :priority')
            ->setParameter('priority', $priority)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return HelpTicket[]
     */
    public function findByCreator(Users $creator): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.creator = :creator')
            ->setParameter('creator', $creator)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return HelpTicket[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
