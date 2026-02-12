<?php

namespace App\Repository;

use App\Entity\Ressource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ressource>
 *
 * @method Ressource|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ressource|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ressource[]    findAll()
 * @method Ressource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RessourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ressource::class);
    }

    /**
     * @return Ressource[] Returns an array of Ressource objects
     */
    public function findWithFilters(array $filters = [], array $sort = []): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.cours', 'c')
            ->addSelect('c');

        // Global Search (Name or Course Title)
        if (!empty($filters['search'])) {
            $qb->andWhere('r.nom LIKE :search OR c.titre LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Specific Filters
        if (!empty($filters['type'])) {
            $qb->andWhere('r.type = :type')
               ->setParameter('type', $filters['type']);
        }
        
        if (!empty($filters['cours'])) {
             // If filter is numeric, assume ID, else assume title string search
            if (is_numeric($filters['cours'])) {
                $qb->andWhere('c.id = :coursId')->setParameter('coursId', $filters['cours']);
            } else {
                $qb->andWhere('c.titre LIKE :coursTitle')->setParameter('coursTitle', '%' . $filters['cours'] . '%');
            }
        }
        
        if (!empty($filters['nom'])) {
            $qb->andWhere('r.nom LIKE :nom')->setParameter('nom', '%' . $filters['nom'] . '%');
        }

        // Sorting
        if (!empty($sort['field'])) {
            $order = strtoupper($sort['order'] ?? 'ASC');
            if (!in_array($order, ['ASC', 'DESC'])) {
                $order = 'ASC';
            }
            
            if (in_array($sort['field'], ['nom', 'type', 'date_de_creation'])) {
                $qb->orderBy('r.' . $sort['field'], $order);
            } elseif ($sort['field'] === 'cours') {
                $qb->orderBy('c.titre', $order);
            }
        } else {
            $qb->orderBy('r.date_de_creation', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Ressource[] Returns an array of Ressource objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Ressource
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
