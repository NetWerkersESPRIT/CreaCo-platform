<?php

namespace App\Repository;

use App\Entity\CoursRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CoursRating>
 */
class CoursRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoursRating::class);
    }

    public function save(CoursRating $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Return average and count for given course ids.
     *
     * @param int[] $ids
     * @return array<int,array{avg: float, count: int}>
     */
    public function getAveragesForCourseIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $qb = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.cours) as course_id, AVG(r.rating) as avg_rating, COUNT(r.id) as cnt')
            ->andWhere('r.cours IN (:ids)')
            ->setParameter('ids', $ids)
            ->groupBy('r.cours');

        $rows = $qb->getQuery()->getResult();
        $out = [];
        foreach ($rows as $row) {
            $out[(int)$row['course_id']] = ['avg' => (float)$row['avg_rating'], 'count' => (int)$row['cnt']];
        }

        return $out;
    }
}
