<?php

namespace App\Repository;

use App\Entity\ContractClause;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContractClause>
 *
 * @method ContractClause|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContractClause|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContractClause[]    findAll()
 * @method ContractClause[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContractClauseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContractClause::class);
    }
}
