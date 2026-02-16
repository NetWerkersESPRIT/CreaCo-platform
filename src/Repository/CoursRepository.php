<?php

namespace App\Repository;

use App\Entity\Cours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cours>
 *
 * @method Cours|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cours|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cours[]    findAll()
 * @method Cours[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cours::class);
    }

    /**
     * @return Cours[] Returns an array of Cours objects
     */
    public function findWithFilters(array $filters = [], array $sort = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.categorie', 'cat')
            ->addSelect('cat');

        // Global Search
        if (!empty($filters['search'])) {
            $qb->andWhere('c.titre LIKE :search OR c.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Specific Filters
        if (!empty($filters['titre'])) {
            $qb->andWhere('c.titre LIKE :titre')
               ->setParameter('titre', '%' . $filters['titre'] . '%');
        }

        if (!empty($filters['categorie'])) {
            if (is_numeric($filters['categorie'])) {
                // Use the association directly for ID
                $qb->andWhere('c.categorie = :catId')
                   ->setParameter('catId', $filters['categorie']);
            } else {
                // Use the join alias for name search
                $qb->andWhere('cat.nom LIKE :catName')
                   ->setParameter('catName', '%' . $filters['categorie'] . '%');
            }
        }

        // Sorting
        if (!empty($sort['field'])) {
            $order = strtoupper($sort['order'] ?? 'ASC');
            if (!in_array($order, ['ASC', 'DESC'])) {
                $order = 'ASC';
            }
            
            if ($sort['field'] === 'category' || $sort['field'] === 'categorie') {
                $qb->orderBy('cat.nom', $order);
            } elseif (property_exists(Cours::class, $sort['field'])) {
                $qb->orderBy('c.' . $sort['field'], $order);
            } else {
                 $qb->orderBy('c.date_de_creation', 'DESC');
            }
        } else {
            $qb->orderBy('c.date_de_creation', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    public function save(Cours $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Cours $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
