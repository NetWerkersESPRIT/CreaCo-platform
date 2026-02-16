<?php

namespace App\Controller;

use App\Repository\UsersRepository;
use App\Repository\EventRepository;
use App\Repository\CollaboratorRepository;
use App\Repository\IdeaRepository;
use App\Repository\MissionRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, \App\Repository\UsersRepository $userRepo, \App\Repository\EventRepository $eventRepo, \App\Repository\CollaboratorRepository $collabRepo, \App\Repository\IdeaRepository $ideaRepo, \App\Repository\MissionRepository $missionRepo, \App\Repository\TaskRepository $taskRepo): Response
    {
        $allowedRoles = ['ROLE_CONTENT_CREATOR', 'ROLE_MANAGER', 'ROLE_MEMBER', 'ROLE_ADMIN'];
        $userRole = $request->getSession()->get('user_role');
        if (!in_array($userRole, $allowedRoles)) {
            $this->addFlash('warning', 'Access restricted.');
            return $this->redirectToRoute('app_auth');
        }

        $stats = [
            'users' => $userRepo->count([]),
            'events' => $eventRepo->count([]),
            'collaborators' => $collabRepo->count([]),
            'ideas' => $ideaRepo->count([]),
            'missions' => $missionRepo->count([]),
            'tasks' => $taskRepo->count([]),
        ];

        // Fetch upcoming events (latest 4)
        $upcomingEvents = $eventRepo->findBy([], ['date' => 'ASC', 'time' => 'ASC'], 4);

        // Fetch latest ideas (latest 3)
        $latestIdeas = $ideaRepo->findBy([], ['id' => 'DESC'], 3);

        return $this->render('home/index.html.twig', [
            'stats' => $stats,
            'upcomingEvents' => $upcomingEvents,
            'latestIdeas' => $latestIdeas,
        ]);
    }
}