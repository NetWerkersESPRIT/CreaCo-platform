<?php

namespace App\Repository;

use App\Entity\Group;
use Doctrine\Bundle\BundleServiceProvider\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository as DoctrineServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Group>
 */
class GroupRepository extends DoctrineServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }
}
