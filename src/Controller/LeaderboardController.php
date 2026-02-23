<?php

namespace App\Controller;

use App\Service\GamificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/leaderboard')]
final class LeaderboardController extends AbstractController
{
    #[Route('', name: 'app_leaderboard', methods: ['GET'])]
    public function index(
        Request $request,
        GamificationService $gamificationService,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Check if user is authenticated
        $allowedRoles = ['ROLE_CONTENT_CREATOR', 'ROLE_MANAGER', 'ROLE_MEMBER', 'ROLE_ADMIN'];
        $userRole = $request->getSession()->get('user_role');
        
        if (!in_array($userRole, $allowedRoles)) {
            $this->addFlash('warning', 'Access restricted.');
            return $this->redirectToRoute('app_auth');
        }

        // Get current user
        $userId = $request->getSession()->get('user_id');
        $currentUser = $userId ? $entityManager->getRepository(\App\Entity\Users::class)->find($userId) : null;

        // Get leaderboard data (top 50)
        $leaderboard = $gamificationService->getLeaderboard(50);

        // Get current user stats & badges
        $userStats = null;
        $userRank = null;
        $userBadges = [];
        if ($currentUser) {
            $userStats = $gamificationService->getUserStats($currentUser);

            // Find user's rank
            foreach ($leaderboard as $index => $entry) {
                if ($entry['id'] == $currentUser->getId()) {
                    $userRank = $index + 1;
                    break;
                }
            }

            $userBadges = $gamificationService->getUserBadges($currentUser);
        }

        return $this->render('front/leaderboard/index.html.twig', [
            'leaderboard' => $leaderboard,
            'currentUser' => $currentUser,
            'userStats' => $userStats,
            'userRank' => $userRank,
            'userBadges' => $userBadges,
        ]);
    }
}

