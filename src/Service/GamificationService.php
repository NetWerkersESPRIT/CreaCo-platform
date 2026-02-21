<?php

namespace App\Service;

use App\Entity\Users;
use App\Entity\Ressource;
use App\Entity\Cours;
use App\Entity\UserRessourceProgress;
use App\Entity\UserCoursProgress;
use App\Repository\UserRessourceProgressRepository;
use App\Repository\UserCoursProgressRepository;
use Doctrine\ORM\EntityManagerInterface;

class GamificationService
{
    // Points configuration
    private const POINTS_PER_RESOURCE_OPENED = 10;
    private const POINTS_PER_COURSE_COMPLETED = 100;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRessourceProgressRepository $ressourceProgressRepo,
        private UserCoursProgressRepository $coursProgressRepo
    ) {
    }

    /**
     * Mark a resource as opened and award points
     */
    public function markResourceAsOpened(Users $user, Ressource $ressource): array
    {
        // Find or create progress record
        $progress = $this->ressourceProgressRepo->findOrCreate($user, $ressource);
        
        $isFirstOpen = !$progress->isOpened();
        
        if ($isFirstOpen) {
            // Mark as opened
            $progress->markAsOpened();
            $this->entityManager->persist($progress);
            
            // Award points
            $user->addPoints(self::POINTS_PER_RESOURCE_OPENED);
            $this->entityManager->persist($user);
            
            // Update course progress
            $this->updateCourseProgress($user, $ressource->getCours());
            
            $this->entityManager->flush();
            
            return [
                'first_open' => true,
                'points_earned' => self::POINTS_PER_RESOURCE_OPENED,
                'total_points' => $user->getPoints()
            ];
        }
        
        return [
            'first_open' => false,
            'points_earned' => 0,
            'total_points' => $user->getPoints()
        ];
    }

    /**
     * Update course progress based on opened resources
     */
    public function updateCourseProgress(Users $user, Cours $cours): void
    {
        $coursProgress = $this->coursProgressRepo->findOrCreate($user, $cours);
        
        // Count opened resources
        $openedCount = $this->ressourceProgressRepo->countOpenedByUserAndCourse($user, $cours);
        $totalCount = count($cours->getRessources());
        
        // Calculate previous completion status
        $wasCompleted = $coursProgress->isCompleted();
        
        // Update progress
        $coursProgress->updateProgress($openedCount, $totalCount);
        $this->entityManager->persist($coursProgress);
        
        // Award bonus points for course completion
        if ($coursProgress->isCompleted() && !$wasCompleted) {
            $user->addPoints(self::POINTS_PER_COURSE_COMPLETED);
            $this->entityManager->persist($user);
        }
    }

    /**
     * Get user's progress for a specific course
     */
    public function getUserCourseProgress(Users $user, Cours $cours): array
    {
        $coursProgress = $this->coursProgressRepo->findOrCreate($user, $cours);
        $totalResources = count($cours->getRessources());
        $openedResources = $this->ressourceProgressRepo->countOpenedByUserAndCourse($user, $cours);
        
        return [
            'total_resources' => $totalResources,
            'opened_resources' => $openedResources,
            'progress_percentage' => $totalResources > 0 ? ($openedResources / $totalResources) * 100 : 0,
            'is_completed' => $coursProgress->isCompleted(),
            'completed_at' => $coursProgress->getCompletedAt()
        ];
    }

    /**
     * Get leaderboard data
     */
    public function getLeaderboard(int $limit = 10): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        return $qb->select('u.id, u.username, u.email, u.points')
            ->from(Users::class, 'u')
            ->where('u.points > 0')
            ->orderBy('u.points', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get user statistics
     */
    public function getUserStats(Users $user): array
    {
        $totalOpened = $this->ressourceProgressRepo->countTotalOpenedByUser($user);
        $completedCourses = $this->coursProgressRepo->countCompletedByUser($user);
        $inProgressCourses = $this->coursProgressRepo->countInProgressByUser($user);
        
        return [
            'points' => $user->getPoints(),
            'total_resources_opened' => $totalOpened,
            'completed_courses' => $completedCourses,
            'in_progress_courses' => $inProgressCourses,
        ];
    }

    /**
     * Check if user has opened a specific resource
     */
    public function hasUserOpenedResource(Users $user, Ressource $ressource): bool
    {
        $progress = $this->ressourceProgressRepo->findOneBy([
            'user' => $user,
            'ressource' => $ressource
        ]);
        
        return $progress && $progress->isOpened();
    }

    /**
     * Get all resources progress for a user in a course
     */
    public function getUserResourcesProgressInCourse(Users $user, Cours $cours): array
    {
        return $this->ressourceProgressRepo->findByUserAndCourse($user, $cours);
    }
}

