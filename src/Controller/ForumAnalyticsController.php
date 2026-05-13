<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use App\Repository\PostReactionRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for Admin Forum Analytics.
 * Provides quantitative data on posts, comments, reactions, and active users.
 */
class ForumAnalyticsController extends AbstractController
{
    #[Route('/admin/forum/analytics', name: 'admin_forum_analytics')]
    public function index(
        Request $request,
        PostRepository $postRepo,
        CommentRepository $commentRepo,
        PostReactionRepository $reactionRepo,
        UsersRepository $userRepo
    ): Response {
        // Security check
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            $this->addFlash('warning', 'Access restricted to administrators.');
            return $this->redirectToRoute('app_auth');
        }

        // 1. Quantitative Counts
        $totalPosts = $postRepo->count([]);
        $totalComments = $commentRepo->count([]);
        $totalReactions = $reactionRepo->count([]);

        // 2. Active Users (Users who have posted or commented)
        $activeUsersCount = $userRepo->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->leftJoin('u.posts', 'p')
            ->leftJoin('u.comments', 'c')
            ->where('p.id IS NOT NULL OR c.id IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        // 3. Historical Data for Charts (Last 7 Days)
        $labels = [];
        $postsData = [];
        $commentsData = [];
        $reactionsData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-$i days");
            $labels[] = $date->format('D d M');
            
            $start = (clone $date)->setTime(0, 0, 0);
            $end = (clone $date)->setTime(23, 59, 59);

            $postsData[] = (int) $postRepo->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->where('p.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getSingleScalarResult();

            $commentsData[] = (int) $commentRepo->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getSingleScalarResult();

            $reactionsData[] = (int) $reactionRepo->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->render('admin/forum_analytics.html.twig', [
            'totalPosts' => $totalPosts,
            'totalComments' => $totalComments,
            'totalReactions' => $totalReactions,
            'activeUsersCount' => $activeUsersCount,
            'chartLabels' => json_encode($labels),
            'postsData' => json_encode($postsData),
            'commentsData' => json_encode($commentsData),
            'reactionsData' => json_encode($reactionsData),
            'app_user' => $userRepo->find($request->getSession()->get('user_id')),
        ]);
    }
}
