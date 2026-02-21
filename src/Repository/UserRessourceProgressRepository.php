<?php

namespace App\Repository;

use App\Entity\UserRessourceProgress;
use App\Entity\Users;
use App\Entity\Ressource;
use App\Entity\Cours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRessourceProgress>
 */
class UserRessourceProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRessourceProgress::class);
    }

    /**
     * Find or create a progress record for a user and ressource
     */
    public function findOrCreate(Users $user, Ressource $ressource): UserRessourceProgress
    {
        $progress = $this->findOneBy([
            'user' => $user,
            'ressource' => $ressource
        ]);

        if (!$progress) {
            $progress = new UserRessourceProgress();
            $progress->setUser($user);
            $progress->setRessource($ressource);
        }

        return $progress;
    }

    /**
     * Get all progress records for a user in a specific course
     */
    public function findByUserAndCourse(Users $user, Cours $cours): array
    {
        return $this->createQueryBuilder('urp')
            ->join('urp.ressource', 'r')
            ->where('urp.user = :user')
            ->andWhere('r.cours = :cours')
            ->setParameter('user', $user)
            ->setParameter('cours', $cours)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count opened resources for a user in a specific course
     */
    public function countOpenedByUserAndCourse(Users $user, Cours $cours): int
    {
        return (int) $this->createQueryBuilder('urp')
            ->select('COUNT(urp.id)')
            ->join('urp.ressource', 'r')
            ->where('urp.user = :user')
            ->andWhere('r.cours = :cours')
            ->andWhere('urp.status = :status')
            ->setParameter('user', $user)
            ->setParameter('cours', $cours)
            ->setParameter('status', 'opened')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get total opened resources count for a user
     */
    public function countTotalOpenedByUser(Users $user): int
    {
        return (int) $this->createQueryBuilder('urp')
            ->select('COUNT(urp.id)')
            ->where('urp.user = :user')
            ->andWhere('urp.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'opened')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get all users with their opened resources count (for leaderboard)
     */
    public function getLeaderboardData(): array
    {
        return $this->createQueryBuilder('urp')
            ->select('u.id, u.username, u.email, COUNT(urp.id) as opened_count')
            ->join('urp.user', 'u')
            ->where('urp.status = :status')
            ->setParameter('status', 'opened')
            ->groupBy('u.id')
            ->orderBy('opened_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics for admin dashboard
     */
    public function getAdminStats(): array
    {
        $qb = $this->createQueryBuilder('urp');
        
        return [
            'total_opened' => (int) $qb->select('COUNT(urp.id)')
                ->where('urp.status = :status')
                ->setParameter('status', 'opened')
                ->getQuery()
                ->getSingleScalarResult(),
            
            'total_not_opened' => (int) $this->createQueryBuilder('urp2')
                ->select('COUNT(urp2.id)')
                ->where('urp2.status = :status')
                ->setParameter('status', 'not_opened')
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }
}

