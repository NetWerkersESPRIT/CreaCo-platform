<?php

namespace App\Repository;

use App\Entity\PostReaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostReaction>
 */
class PostReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostReaction::class);
    }

    public function countByPostAndType(int $postId): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.type, COUNT(r.id) as count')
            ->where('r.post = :postId')
            ->setParameter('postId', $postId)
            ->groupBy('r.type');

        $results = $qb->getQuery()->getArrayResult();
        
        $counts = [
            'like' => 0,
            'love' => 0,
            'haha' => 0,
            'wow' => 0,
            'sad' => 0,
            'angry' => 0,
            'total' => 0
        ];

        foreach ($results as $result) {
            $counts[$result['type']] = (int) $result['count'];
            $counts['total'] += (int) $result['count'];
        }

        return $counts;
    }
}
