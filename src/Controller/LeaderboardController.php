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
        $allBadges = [];
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
        // load all badge definitions for sticker display
        $badgeRepo = $entityManager->getRepository(\App\Entity\Badge::class);
        $allBadgeEntities = $badgeRepo->findAll();

        // map codes to filenames in uploads folder
        $fileMap = [
            'explorateur' => 'explorer.png',
            'finisseur' => 'finisher.png',
            'maitre_cours' => 'course_master.png',
            'janvier_streaker' => 'january.png',
            'février_streaker' => 'february.png',
            'mars_streaker' => 'march.png',
            'avril_streaker' => 'april.png',
            'mai_streaker' => 'may.png',
            'juin_streaker' => 'june.png',
            'juillet_streaker' => 'july.png',
            'août_streaker' => 'august.png',
            'septembre_streaker' => 'september.png',
            'octobre_streaker' => 'october.png',
            'novembre_streaker' => 'november.png',
            'décembre_streaker' => 'december.png',
        ];

        // Add file mapping to user badges
        foreach ($userBadges as &$badge) {
            $badge['file'] = $fileMap[$badge['code']] ?? 'default.png';
        }

        foreach ($allBadgeEntities as $badgeEntity) {
            $code = $badgeEntity->getCode();
            $file = $fileMap[$code] ?? preg_replace('/[^a-z0-9_\-]/', '', str_replace(' ', '_', strtolower($code))) . '.png';
            $allBadges[] = [
                'code' => $code,
                'name' => $badgeEntity->getName(),
                'file' => $file,
            ];
        }

        return $this->render('front/leaderboard/index.html.twig', [
            'leaderboard' => $leaderboard,
            'currentUser' => $currentUser,
            'userStats' => $userStats,
            'userRank' => $userRank,
            'userBadges' => $userBadges,
            'allBadges' => $allBadges,
        ]);
    }
}

