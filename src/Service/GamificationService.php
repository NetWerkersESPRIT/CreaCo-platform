<?php

namespace App\Service;

use App\Entity\Users;
use App\Entity\Ressource;
use App\Entity\Cours;
use App\Entity\CategorieCours;
use App\Entity\UserRessourceProgress;
use App\Entity\UserCoursProgress;
use App\Repository\UserRessourceProgressRepository;
use App\Repository\UserCoursProgressRepository;
use App\Repository\UserStreakDayRepository;
use App\Repository\BadgeRepository;
use App\Repository\UserBadgeRepository;
use Doctrine\ORM\EntityManagerInterface;

class GamificationService
{
    // Points / XP configuration
    private const POINTS_PER_RESOURCE_OPENED = 10;
    private const POINTS_PER_COURSE_COMPLETED = 100;

    // Badges configuration
    private const BADGE_EXPLORATEUR_CODE = 'explorateur';
    private const BADGE_EXPLORATEUR_RESOURCES_THRESHOLD = 5;

    private const BADGE_FINISSEUR_CODE = 'finisseur';
    private const BADGE_FINISSEUR_COURSES_THRESHOLD = 1;

    private const BADGE_MAITRE_COURS_CODE = 'maitre_cours';
    private const BADGE_MAITRE_COURS_THRESHOLD = 5;

    // Monthly streak badges - one for each month
    private const BADGE_JANVIER_CODE = 'janvier_streaker';
    private const BADGE_FÉVRIER_CODE = 'février_streaker';
    private const BADGE_MARS_CODE = 'mars_streaker';
    private const BADGE_AVRIL_CODE = 'avril_streaker';
    private const BADGE_MAI_CODE = 'mai_streaker';
    private const BADGE_JUIN_CODE = 'juin_streaker';
    private const BADGE_JUILLET_CODE = 'juillet_streaker';
    private const BADGE_AOÛT_CODE = 'août_streaker';
    private const BADGE_SEPTEMBRE_CODE = 'septembre_streaker';
    private const BADGE_OCTOBRE_CODE = 'octobre_streaker';
    private const BADGE_NOVEMBRE_CODE = 'novembre_streaker';
    private const BADGE_DÉCEMBRE_CODE = 'décembre_streaker';

    private const MONTHLY_STREAK_CODES = [
        1 => self::BADGE_JANVIER_CODE,
        2 => self::BADGE_FÉVRIER_CODE,
        3 => self::BADGE_MARS_CODE,
        4 => self::BADGE_AVRIL_CODE,
        5 => self::BADGE_MAI_CODE,
        6 => self::BADGE_JUIN_CODE,
        7 => self::BADGE_JUILLET_CODE,
        8 => self::BADGE_AOÛT_CODE,
        9 => self::BADGE_SEPTEMBRE_CODE,
        10 => self::BADGE_OCTOBRE_CODE,
        11 => self::BADGE_NOVEMBRE_CODE,
        12 => self::BADGE_DÉCEMBRE_CODE,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRessourceProgressRepository $ressourceProgressRepo,
        private UserCoursProgressRepository $coursProgressRepo,
        private UserStreakDayRepository $streakDayRepo,
        private BadgeRepository $badgeRepo,
        private UserBadgeRepository $userBadgeRepo
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

            // after update we can award resource milestones
            $totalOpened = $this->ressourceProgressRepo->countTotalOpenedByUser($user);
            if ($totalOpened >= self::BADGE_EXPLORATEUR_RESOURCES_THRESHOLD) {
                $this->awardBadge($user, self::BADGE_EXPLORATEUR_CODE);
            }

            // Mark streak day (Duolingo-style: one resource per day marks the day)
            $this->markStreakDay($user);
            
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
     * Mark today's streak day for the user if not already marked.
     */
    private function markStreakDay(Users $user): void
    {
        $today = new \DateTime();

        // check existing
        $existing = $this->streakDayRepo->findOneByUserAndDate($user, $today);
        if ($existing) {
            return;
        }

        // if no entry for today, create one
        $streakDay = new \App\Entity\UserStreakDay();
        $streakDay->setUser($user);
        $streakDay->setDay($today);

        $this->entityManager->persist($streakDay);
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

            // badges: finisseur and maitre
            $completedCourses = $this->coursProgressRepo->countCompletedByUser($user);
            if ($completedCourses >= self::BADGE_FINISSEUR_COURSES_THRESHOLD) {
                $this->awardBadge($user, self::BADGE_FINISSEUR_CODE);
            }
            if ($completedCourses >= self::BADGE_MAITRE_COURS_THRESHOLD) {
                $this->awardBadge($user, self::BADGE_MAITRE_COURS_CODE);
            }
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
     * Award a badge to a user if not already awarded.
     *
     * @return bool true if the badge was newly created/awarded
     */
    public function awardBadge(Users $user, string $code, ?array $metadata = null): bool
    {
        // find or create badge definition
        $badge = $this->badgeRepo->findOneByCode($code);
        if (!$badge) {
            $badge = new \App\Entity\Badge();
            $badge->setCode($code);
            $badge->setName(ucfirst(str_replace('_', ' ', $code)));
            $badge->setDescription('Badge '.$code);
            $this->entityManager->persist($badge);
            // flush later
        }

        // check if user already has it
        if ($this->userBadgeRepo->findOneByUserAndBadge($user, $badge)) {
            return false;
        }

        $userBadge = new \App\Entity\UserBadge();
        $userBadge->setUser($user);
        $userBadge->setBadge($badge);
        if ($metadata !== null) {
            $userBadge->setMetadata($metadata);
        }
        $this->entityManager->persist($userBadge);
        $this->entityManager->flush();
        return true;
    }

    /**
     * Check if a user has a specific badge
     */
    public function hasBadge(Users $user, string $code): bool
    {
        $badge = $this->badgeRepo->findOneByCode($code);
        if (!$badge) {
            return false;
        }
        return (bool)$this->userBadgeRepo->findOneByUserAndBadge($user, $badge);
    }

    /**
     * Get the monthly streak badge code for a given month
     */
    private function getMonthlyStreakBadgeCode(int $month): string
    {
        return self::MONTHLY_STREAK_CODES[$month] ?? 'janvier_streaker';
    }

    /**
     * Award monthly streak badges for a specific month
     */
    public function awardMonthlyStreakBadges(int $year, int $month): array
    {
        // returns list of user ids who were awarded
        $start = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $end = (clone $start)->modify('first day of next month');
        $daysInMonth = (int)$start->format('t');

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')
            ->from(Users::class, 'u')
            ->join('App\\Entity\\UserStreakDay', 'usd', 'WITH', 'usd.user = u')
            ->where('usd.day >= :start')
            ->andWhere('usd.day < :end')
            ->groupBy('u.id')
            ->having('COUNT(usd.id) = :days')
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->setParameter('days', $daysInMonth);

        $users = $qb->getQuery()->getResult();
        $awarded = [];
        $badgeCode = $this->getMonthlyStreakBadgeCode($month);
        
        foreach ($users as $u) {
            if ($this->awardBadge($u, $badgeCode, ['year' => $year, 'month' => $month])) {
                $awarded[] = $u->getId();
            }
        }
        return $awarded;
    }

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
     * Get unlocked badges for a user based on their statistics.
     *
     * Badges are computed dynamically; no persistence layer is used.
     */
    /**
     * Returns persisted badges for the user.
     */
    public function getUserBadges(Users $user): array
    {
        $userBadges = $this->userBadgeRepo->findByUser($user);
        $result = [];
        foreach ($userBadges as $ub) {
            $b = $ub->getBadge();
            $result[] = [
                'code' => $b->getCode(),
                'name' => $b->getName(),
                'description' => $b->getDescription(),
                'icon' => $b->getIcon(),
                'rarity' => $b->getRarity(),
                'awarded_at' => $ub->getAwardedAt(),
                'metadata' => $ub->getMetadata(),
            ];
        }
        return $result;
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

    /**
     * Check if the user has completed all resources in all courses of a category.
     * Returns true only when every course in the category has been completed (all resources opened).
     * Courses with zero resources are considered completed. Completion is computed from current
     * opened/total counts so it stays correct even if completed_at was not set.
     */
    public function hasUserCompletedCategory(Users $user, CategorieCours $category): bool
    {
        $courses = $category->getCours();
        if ($courses->isEmpty()) {
            return false;
        }
        foreach ($courses as $cours) {
            $progress = $this->getUserCourseProgress($user, $cours);
            $total = $progress['total_resources'];
            $opened = $progress['opened_resources'];
            // Completed if: no resources (nothing to do) OR all resources opened
            $courseCompleted = ($total === 0) || ($total > 0 && $opened >= $total);
            if (!$courseCompleted) {
                return false;
            }
        }
        return true;
    }
}

