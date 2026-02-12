<?php

namespace App\Repository;

use App\Entity\CategorieCours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieCours>
 *
 * @method CategorieCours|null find($id, $lockMode = null, $lockVersion = null)
 * @method CategorieCours|null findOneBy(array $criteria, array $orderBy = null)
 * @method CategorieCours[]    findAll()
 * @method CategorieCours[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategorieCoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieCours::class);
    }

    /**
     * @return CategorieCours[] Returns an array of CategorieCours objects
     */
    public function searchByName(string $search): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.nom LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->getQuery()
            ->getResult();
    }

    public function save(CategorieCours $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CategorieCours $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
