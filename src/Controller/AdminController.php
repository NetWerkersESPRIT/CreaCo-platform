<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\UserType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(
        Request $request,
        EntityManagerInterface $em,
        \App\Repository\UsersRepository $userRepo,
        \App\Repository\IdeaRepository $ideaRepo,
        \App\Repository\MissionRepository $missionRepo,
        \App\Repository\TaskRepository $taskRepo,
        \App\Repository\EventRepository $eventRepo,
        \App\Repository\PostRepository $postRepo,
        \App\Repository\CommentRepository $commentRepo,
        \App\Repository\CoursRepository $coursRepo
    ): Response {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }

        // 1. Last 10 created accounts
        $recentUsers = $userRepo->findBy([], ['id' => 'DESC'], 10);

        // Period boundaries
        $today = new \DateTime('today');
        $thisMonth = new \DateTime('first day of this month');
        $thisYear = new \DateTime('first day of January this year');

        // Helper to get stats
        $getStats = function ($repo, $dateField = 'createdAt') use ($today, $thisMonth, $thisYear) {
            $qb = $repo->createQueryBuilder('e');
            return [
                'total' => (int) $repo->createQueryBuilder('e')->select('count(e.id)')->getQuery()->getSingleScalarResult(),
                'today' => (int) $repo->createQueryBuilder('e')->select('count(e.id)')->where("e.$dateField >= :today")->setParameter('today', $today)->getQuery()->getSingleScalarResult(),
                'month' => (int) $repo->createQueryBuilder('e')->select('count(e.id)')->where("e.$dateField >= :month")->setParameter('month', $thisMonth)->getQuery()->getSingleScalarResult(),
                'year' => (int) $repo->createQueryBuilder('e')->select('count(e.id)')->where("e.$dateField >= :year")->setParameter('year', $thisYear)->getQuery()->getSingleScalarResult(),
            ];
        };

        $workflowStats = [
            'ideas' => $getStats($ideaRepo),
            'missions' => $getStats($missionRepo),
            'tasks' => $getStats($taskRepo),
        ];

        $eventStats = $getStats($eventRepo);

        $forumStats = [
            'posts' => $getStats($postRepo),
            'comments' => $getStats($commentRepo),
        ];

        $courseStats = $getStats($coursRepo, 'date_de_creation');
        $topCourses = $coursRepo->findBy([], ['views' => 'DESC'], 5);

        // 2. Forum Historical Data (Last 7 Days)
        $forumHistoricalLabels = [];
        $forumHistoricalPosts = [];
        $forumHistoricalComments = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-$i days");
            $forumHistoricalLabels[] = $date->format('D d M');

            $dayStart = (clone $date)->setTime(0, 0, 0);
            $dayEnd = (clone $date)->setTime(23, 59, 59);

            $forumHistoricalPosts[] = (int) $postRepo->createQueryBuilder('p')
                ->select('count(p.id)')
                ->where('p.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $dayStart)
                ->setParameter('end', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();

            $forumHistoricalComments[] = (int) $commentRepo->createQueryBuilder('c')
                ->select('count(c.id)')
                ->where('c.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $dayStart)
                ->setParameter('end', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->render('admin/dashboard.html.twig', [
            'recentUsers' => $recentUsers,
            'workflowStats' => $workflowStats,
            'eventStats' => $eventStats,
            'forumStats' => $forumStats,
            'courseStats' => $courseStats,
            'topCourses' => $topCourses,
            'forumHistoricalLabels' => json_encode($forumHistoricalLabels),
            'forumHistoricalPosts' => json_encode($forumHistoricalPosts),
            'forumHistoricalComments' => json_encode($forumHistoricalComments),
            'app_user' => $userRepo->find($request->getSession()->get('user_id')),
        ]);
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(Request $request, UsersRepository $userRepository, \App\Repository\PostRepository $postRepository): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            $this->addFlash('warning', 'Access restricted to administrators.');
            return $this->redirectToRoute('app_auth');
        }

        $pendingCount = $postRepository->countPending();

        $users = $userRepository->createQueryBuilder('u')
            ->where('u.role != :role')
            ->setParameter('role', 'ROLE_ADMIN')
            ->getQuery()
            ->getResult();

        return $this->render('admin/admin.html.twig', [
            'users' => $users,
            'pendingCount' => $pendingCount,
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_user_edit')]
    public function edit(
        Users $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }

        $oldPassword = $user->getPassword();
        $form = $this->createForm(UserType::class, $user, [
            'optional_password' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string|null $newPassword */
            $newPassword = $form->get('password')->getData();
            if (empty($newPassword)) {
                $user->setPassword($oldPassword);
            } else {
                // Ensure $newPassword is a string for hashPassword
                $hashedPassword = $passwordHasher->hashPassword($user, (string) $newPassword);
                $user->setPassword($hashedPassword);
            }
            $em->flush();

            return $this->redirectToRoute('app_admin');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/user/delete/{id}', name: 'user_delete')]
    public function delete(Request $request, int $id, EntityManagerInterface $em, UsersRepository $repo): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }
        $user = $repo->find($id);

        if ($user) {
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin');
    }
}
