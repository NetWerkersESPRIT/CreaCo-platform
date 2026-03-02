<?php

namespace App\Repository;

use App\Entity\UserBadge;
use App\Entity\Users;
use App\Entity\Badge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBadge>
 */
class UserBadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBadge::class);
    }

    public function findByUser(Users $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function findOneByUserAndBadge(Users $user, Badge $badge): ?UserBadge
    {
        return $this->findOneBy([
            'user' => $user,
            'badge' => $badge,
        ]);
    }
}
