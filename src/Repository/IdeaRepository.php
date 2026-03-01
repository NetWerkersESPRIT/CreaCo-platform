<?php

namespace App\Repository;

use App\Entity\Idea;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Idea>
 */
class IdeaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Idea::class);
    }

    public function findRankedIdeas(array $weights, array $trendingIds, array $usedIds, int $limit): array
    {
        $qb = $this->createQueryBuilder('i');

        // 1. Exclude ideas the user has already used
        if (!empty($usedIds)) {
            $qb->andWhere('i.id NOT IN (:usedIds)')
                ->setParameter('usedIds', $usedIds);
        }

        // 2. Build the Scoring Logic (The "ML" part)
        // We start with a base score of 0
        $scoreFormula = '0';

        // Boost based on User Category History
        foreach ($weights as $category => $weight) {
            // Sanitize category string for parameter naming
            $paramName = 'cat_' . md5($category);
            $scoreFormula .= " + (CASE WHEN i.category = :$paramName THEN $weight ELSE 0 END)";
            $qb->setParameter($paramName, $category);
        }

        // Boost if the idea is currently "Hot" (Trending)
        if (!empty($trendingIds)) {
            $scoreFormula .= " + (CASE WHEN i.id IN (:trendingIds) THEN 5 ELSE 0 END)";
            $qb->setParameter('trendingIds', $trendingIds);
        }

        // 3. Add the calculated score to the selection and sort by it
        return $qb->addSelect("($scoreFormula) as HIDDEN score")
            ->orderBy('score', 'DESC')
            ->addOrderBy('i.id', 'DESC') // Secondary sort for consistency
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
//     * @return Idea[] Returns an array of Idea objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    //    public function findOneBySomeField($value): ?Idea
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
