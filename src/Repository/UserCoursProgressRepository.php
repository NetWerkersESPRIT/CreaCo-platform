<?php

namespace App\Repository;

use App\Entity\UserCoursProgress;
use App\Entity\Users;
use App\Entity\Cours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCoursProgress>
 */
class UserCoursProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCoursProgress::class);
    }

    /**
     * Find or create a progress record for a user and course
     */
    public function findOrCreate(Users $user, Cours $cours): UserCoursProgress
    {
        $progress = $this->findOneBy([
            'user' => $user,
            'cours' => $cours
        ]);

        if (!$progress) {
            $progress = new UserCoursProgress();
            $progress->setUser($user);
            $progress->setCours($cours);
            $progress->setTotalRessources(count($cours->getRessources()));
        }

        return $progress;
    }

    /**
     * Get all progress records for a user
     */
    public function findByUser(Users $user): array
    {
        return $this->createQueryBuilder('ucp')
            ->where('ucp.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ucp.progress_percentage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get completed courses count for a user
     */
    public function countCompletedByUser(Users $user): int
    {
        return (int) $this->createQueryBuilder('ucp')
            ->select('COUNT(ucp.id)')
            ->where('ucp.user = :user')
            ->andWhere('ucp.completed_at IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get in-progress courses count for a user
     */
    public function countInProgressByUser(Users $user): int
    {
        return (int) $this->createQueryBuilder('ucp')
            ->select('COUNT(ucp.id)')
            ->where('ucp.user = :user')
            ->andWhere('ucp.progress_percentage > 0')
            ->andWhere('ucp.completed_at IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get leaderboard data based on completed courses
     */
    public function getLeaderboardByCompletedCourses(): array
    {
        return $this->createQueryBuilder('ucp')
            ->select('u.id, u.username, u.email, COUNT(ucp.id) as completed_courses')
            ->join('ucp.user', 'u')
            ->where('ucp.completed_at IS NOT NULL')
            ->groupBy('u.id')
            ->orderBy('completed_courses', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics for admin dashboard
     */
    public function getAdminStats(): array
    {
        return [
            'total_enrollments' => (int) $this->createQueryBuilder('ucp')
                ->select('COUNT(ucp.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            
            'total_completed' => (int) $this->createQueryBuilder('ucp2')
                ->select('COUNT(ucp2.id)')
                ->where('ucp2.completed_at IS NOT NULL')
                ->getQuery()
                ->getSingleScalarResult(),
            
            'average_progress' => (float) $this->createQueryBuilder('ucp3')
                ->select('AVG(ucp3.progress_percentage)')
                ->getQuery()
                ->getSingleScalarResult() ?? 0,
        ];
    }

    /**
     * Get detailed progress for all users (admin view)
     */
    public function getAllUsersProgress(): array
    {
        return $this->createQueryBuilder('ucp')
            ->select('u.id as user_id, u.username, u.email, c.id as cours_id, c.titre as cours_titre, 
                      ucp.progress_percentage, ucp.opened_ressources, ucp.total_ressources, 
                      ucp.completed_at, ucp.updated_at')
            ->join('ucp.user', 'u')
            ->join('ucp.cours', 'c')
            ->orderBy('u.username', 'ASC')
            ->addOrderBy('ucp.progress_percentage', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

