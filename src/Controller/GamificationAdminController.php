<?php

namespace App\Controller;

use App\Repository\UserRessourceProgressRepository;
use App\Repository\UserCoursProgressRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/gamification')]
final class GamificationAdminController extends AbstractController
{
    #[Route('', name: 'app_admin_gamification', methods: ['GET'])]
    public function index(
        Request $request,
        UserRessourceProgressRepository $ressourceProgressRepo,
        UserCoursProgressRepository $coursProgressRepo,
        UsersRepository $usersRepo
    ): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        // Get statistics
        $ressourceStats = $ressourceProgressRepo->getAdminStats();
        $coursStats = $coursProgressRepo->getAdminStats();
        
        // Get top users by points
        $topUsers = $usersRepo->createQueryBuilder('u')
            ->where('u.points > 0')
            ->orderBy('u.points', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Get all users progress details
        $allProgress = $coursProgressRepo->getAllUsersProgress();

        // Calculate additional stats
        $totalUsers = $usersRepo->count([]);
        $activeUsers = $usersRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.points > 0')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('back/gamification/index.html.twig', [
            'ressource_stats' => $ressourceStats,
            'cours_stats' => $coursStats,
            'top_users' => $topUsers,
            'all_progress' => $allProgress,
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
        ]);
    }

    #[Route('/user/{id}', name: 'app_admin_gamification_user', methods: ['GET'])]
    public function userDetails(
        Request $request,
        int $id,
        UsersRepository $usersRepo,
        UserCoursProgressRepository $coursProgressRepo,
        UserRessourceProgressRepository $ressourceProgressRepo
    ): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        $user = $usersRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Get user's course progress
        $coursProgress = $coursProgressRepo->findByUser($user);
        
        // Get total opened resources
        $totalOpenedResources = $ressourceProgressRepo->countTotalOpenedByUser($user);
        
        // Get completed courses count
        $completedCourses = $coursProgressRepo->countCompletedByUser($user);
        
        // Get in-progress courses count
        $inProgressCourses = $coursProgressRepo->countInProgressByUser($user);

        return $this->render('back/gamification/user_details.html.twig', [
            'user' => $user,
            'cours_progress' => $coursProgress,
            'total_opened_resources' => $totalOpenedResources,
            'completed_courses' => $completedCourses,
            'in_progress_courses' => $inProgressCourses,
        ]);
    }
}

