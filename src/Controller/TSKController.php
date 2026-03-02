<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Idea;
use App\Entity\Mission;
use App\Entity\Task;
use App\Entity\Users;
use App\Form\IdeaType;
use App\Form\MissionType;
use App\Form\TaskType;
use App\Repository\IdeaRepository;
use App\Repository\MissionRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\MissionDescGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TSKController extends AbstractController
{

    #[Route('/idea', name: 'app_idea_index', methods: ['GET'])]
    public function ideaIndex(Request $request, IdeaRepository $ideaRepository, \App\Service\IdeaRecommendationService $recommendationService, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $user = $userId ? $entityManager->getRepository(Users::class)->find($userId) : null;

        if (!$user) {
            $recommendedIdeas = $ideaRepository->findBy([], ['id' => 'DESC'], 6);
        } else {
            $recommendedIdeas = $recommendationService->getHybridRecommendations($user, 6);
        }

        $trendingIdeas = $recommendationService->getTrendingIdeas(6);

        return $this->render('tsk/index.html.twig', [
            'ideas' => $ideaRepository->findAll(),
            'recommended_ideas' => $recommendedIdeas,
            'trending_ideas' => $trendingIdeas,
            'current_period' => 'today',
        ]);
    }

    #[Route('/idea/new', name: 'app_idea_new', methods: ['GET', 'POST'])]
    public function ideaNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        $idea = new Idea();
        $form = $this->createForm(IdeaType::class, $idea);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $idea->setCreatedAt(new \DateTimeImmutable());

            $session = $request->getSession();
            $userId = $session->get('user_id');
            $user = $userId ? $entityManager->getRepository(Users::class)->find($userId) : null;

            if ($user) {
                $idea->setCreator($user);
            }

            $entityManager->persist($idea);
            $entityManager->flush();

            $this->addFlash('success', 'Idée créée avec succès !');

            if ($request->query->get('from_mission')) {
                return $this->redirectToRoute('app_mission_new', [
                    'idea_id' => $idea->getId(),
                    'm_title' => $request->query->get('m_title'),
                    'm_desc' => $request->query->get('m_desc'),
                    'm_state' => $request->query->get('m_state'),
                ]);
            }

            return $this->redirectToRoute('app_idea_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tsk/new.html.twig', [
            'idea' => $idea,
            'form' => $form,
        ]);
    }

    #[Route('/idea/trending', name: 'app_idea_trending', methods: ['GET'])]
    public function trendingIdeas(Request $request, \App\Service\IdeaRecommendationService $recommendationService): Response
    {
        $period = $request->query->get('period', 'week');
        $trendingIdeas = $recommendationService->getTrendingIdeas(6, $period);

        return $this->render('tsk/_trending_ideas.html.twig', [
            'trending_ideas' => $trendingIdeas,
        ]);
    }

    #[Route('/idea/{id}', name: 'app_idea_show', methods: ['GET'])]
    public function ideaShow(Idea $idea): Response
    {
        return $this->render('tsk/show.html.twig', [
            'idea' => $idea,
        ]);
    }

    #[Route('/idea/{id}/edit', name: 'app_idea_edit', methods: ['GET', 'POST'])]
    public function ideaEdit(Request $request, Idea $idea, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(IdeaType::class, $idea);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Idea updated successfully!');

            return $this->redirectToRoute('app_idea_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tsk/edit.html.twig', [
            'idea' => $idea,
            'form' => $form,
        ]);
    }

    #[Route('/idea/{id}', name: 'app_idea_delete', methods: ['POST'])]
    public function ideaDelete(Request $request, Idea $idea, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $idea->getId(), $request->request->get('_token'))) {
            $entityManager->remove($idea);
            $entityManager->flush();
            $this->addFlash('success', 'Idea deleted successfully!');
        }

        return $this->redirectToRoute('app_idea_index', [], Response::HTTP_SEE_OTHER);
    }

    // --- MISSION ---

    #[Route('/mission', name: 'app_mission_index', methods: ['GET'])]
    public function missionIndex(MissionRepository $missionRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $userRole = $request->getSession()->get('user_role');
        $userId = $request->getSession()->get('user_id');

        if ($userRole === 'ROLE_ADMIN') {
            $missions = $missionRepository->findAll();
        } elseif ($userId) {
            $currentUser = $entityManager->getRepository(Users::class)->find($userId);
            if ($currentUser) {
                $groupIds = $currentUser->getGroups()->map(fn($g) => $g->getId())->toArray();

                // Also include IDs of groups owned by the user
                foreach ($entityManager->getRepository(Group::class)->findBy(['owner' => $currentUser]) as $ownedGroup) {
                    $groupIds[] = $ownedGroup->getId();
                }
                $groupIds = array_unique($groupIds);

                $qb = $missionRepository->createQueryBuilder('m')
                    ->innerJoin('m.assignedBy', 'u')
                    ->leftJoin('u.groups', 'g')
                    ->leftJoin('App\Entity\Group', 'go', 'WITH', 'go.owner = u');

                $condition = 'u.id = :userId';
                if (!empty($groupIds)) {
                    $condition .= ' OR g.id IN (:groupIds) OR go.id IN (:groupIds)';
                    $qb->setParameter('groupIds', $groupIds);
                }

                $missions = $qb->where($condition)
                    ->setParameter('userId', $userId)
                    ->getQuery()
                    ->getResult();
            } else {
                $missions = [];
            }
        } else {
            $missions = [];
        }

        $calendarData = [];
        foreach ($missions as $mission) {
            $calendarData[] = [
                'id' => $mission->getId(),
                'title' => $mission->getTitle(),
                'start' => $mission->getMissionDate() ? $mission->getMissionDate()->format('Y-m-d H:i:s') : $mission->getCreatedAt()->format('Y-m-d H:i:s'),
                'url' => $this->generateUrl('app_mission_show', ['id' => $mission->getId()]),
                'backgroundColor' => $mission->getState() === 'completed' ? '#10b981' : ($mission->getState() === 'in_progress' ? '#3b82f6' : '#9333ea'),
                'borderColor' => $mission->getState() === 'completed' ? '#10b981' : ($mission->getState() === 'in_progress' ? '#3b82f6' : '#9333ea'),
                'description' => $mission->getDescription(),
                'state' => $mission->getState(),
                'idea' => $mission->getImplementIdea() ? $mission->getImplementIdea()->getTitle() : 'N/A',
                'creator' => $mission->getAssignedBy() ? $mission->getAssignedBy()->getUsername() : 'Unknown',
            ];
        }

        return $this->render('mission/index.html.twig', [
            'missions' => $missions,
            'calendar_data' => json_encode($calendarData),
        ]);
    }

    #[Route('/mission/new', name: 'app_mission_new', methods: ['GET', 'POST'])]
    public function missionNew(Request $request, EntityManagerInterface $entityManager, MissionDescGenerator $generator): Response
    {
        $mission = new Mission();

        $ideaId = $request->query->get('idea_id');
        if ($ideaId) {
            $idea = $entityManager->getRepository(Idea::class)->find($ideaId);
            if ($idea) {
                $mission->setImplementIdea($idea);
            }
        }

        if ($request->query->has('m_title')) {
            $mission->setTitle($request->query->get('m_title'));
        }
        if ($request->query->has('m_desc')) {
            $mission->setDescription($request->query->get('m_desc'));
        }
        if ($request->query->has('m_state')) {
            $mission->setState($request->query->get('m_state'));
        }

        $dateParam = $request->query->get('date');
        if ($dateParam) {
            try {
                $mission->setMissionDate(new \DateTime($dateParam));
            } catch (\Exception $e) {
                // Invalid date format, ignore
            }
        }

        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mission->setCreatedAt(new \DateTimeImmutable());

            $session = $request->getSession();
            $userId = $session->get('user_id');
            $user = $userId ? $entityManager->getRepository(Users::class)->find($userId) : null;

            if ($user) {
                $mission->setAssignedBy($user);
            }

            // AI Description Generation: if description is empty and idea is present
            if (!$mission->getDescription() && $mission->getImplementIdea()) {
                $idea = $mission->getImplementIdea();
                $aiDesc = $generator->generate(
                    $mission->getTitle(),
                    $idea->getTitle(),
                    $idea->getDescription(),
                    $idea->getCategory()
                );
                $mission->setDescription($aiDesc);
            }

            // Track Idea Usage
            if ($mission->getImplementIdea() && $user) {
                $ideaUsage = new \App\Entity\IdeaUsage();
                $ideaUsage->setIdea($mission->getImplementIdea());
                $ideaUsage->setUser($user);
                $ideaUsage->setDateUsed(new \DateTimeImmutable());
                $entityManager->persist($ideaUsage);

                $mission->getImplementIdea()->setLastUsed(new \DateTime());
            }

            $entityManager->persist($mission);
            $entityManager->flush();

            $this->addFlash('success', 'Mission created successfully!');

            // Check if we need to return to task creation
            $returnTo = $request->query->get('return_to');
            if ($returnTo === 'task') {
                // Build redirect URL with mission_id and preserved task data
                $params = ['mission_id' => $mission->getId()];

                if ($request->query->has('t_title')) {
                    $params['title'] = $request->query->get('t_title');
                }
                if ($request->query->has('t_desc')) {
                    $params['description'] = $request->query->get('t_desc');
                }
                if ($request->query->has('t_date')) {
                    $params['t_date'] = $request->query->get('t_date');
                }
                if ($request->query->has('t_time')) {
                    $params['t_time'] = $request->query->get('t_time');
                }
                if ($request->query->has('t_assumedBy')) {
                    $params['assumedBy'] = $request->query->get('t_assumedBy');
                }

                return $this->redirectToRoute('app_task_new', $params, Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_mission_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mission/new.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }

    #[Route('/mission/ai-generate', name: 'app_mission_ai_generate', methods: ['POST'])]
    public function aiGenerateDescription(Request $request, MissionDescGenerator $generator, IdeaRepository $ideaRepository): Response
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? 'New Mission';
        $ideaId = $data['ideaId'] ?? null;

        $idea = $ideaId ? $ideaRepository->find($ideaId) : null;
        $ideaTitle = $idea ? $idea->getTitle() : 'General Project';
        $ideaDescription = $idea ? $idea->getDescription() : null;
        $ideaCategory = $idea ? $idea->getCategory() : null;

        $aiDesc = $generator->generate($title, $ideaTitle, $ideaDescription, $ideaCategory);

        return $this->json(['description' => $aiDesc]);
    }

    #[Route('/mission/api/recommend', name: 'app_mission_recommend_idea', methods: ['GET'])]
    public function recommendIdea(Request $request, \App\Service\IdeaRecommendationService $recommendationService, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $user = $userId ? $entityManager->getRepository(Users::class)->find($userId) : null;

        if (!$user) {
            $ideas = $entityManager->getRepository(Idea::class)->findBy([], ['id' => 'DESC'], 1);
            $recommendedIdea = !empty($ideas) ? $ideas[0] : null;
        } else {
            $recommendations = $recommendationService->getHybridRecommendations($user, 1);
            $recommendedIdea = !empty($recommendations) ? $recommendations[0] : null;
        }

        if (!$recommendedIdea) {
            return $this->json(['error' => 'No ideas available'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $recommendedIdea->getId(),
            'title' => $recommendedIdea->getTitle()
        ]);
    }

    #[Route('/mission/{id}', name: 'app_mission_show', methods: ['GET'])]
    public function missionShow(Mission $mission, Request $request, EntityManagerInterface $entityManager): Response
    {
        $userRole = $request->getSession()->get('user_role');
        $userId = $request->getSession()->get('user_id');

        // Only show if admin, creator, or in same group
        if ($userRole !== 'ROLE_ADMIN') {
            $currentUser = $userId ? $entityManager->getRepository(Users::class)->find($userId) : null;
            if (!$this->isCoworker($currentUser, $mission->getAssignedBy())) {
                throw $this->createAccessDeniedException('You do not have access to this mission.');
            }
        }

        return $this->render('mission/show.html.twig', [
            'mission' => $mission,
        ]);
    }

    #[Route('/mission/{id}/edit', name: 'app_mission_edit', methods: ['GET', 'POST'])]
    public function missionEdit(Request $request, Mission $mission, EntityManagerInterface $entityManager): Response
    {
        $userRole = $request->getSession()->get('user_role');
        $userId = $request->getSession()->get('user_id');

        // Only allow edit if admin, creator, or in same group
        if ($userRole !== 'ROLE_ADMIN') {
            $currentUser = $userId ? $entityManager->getRepository(Users::class)->find($userId) : null;
            if (!$this->isCoworker($currentUser, $mission->getAssignedBy())) {
                throw $this->createAccessDeniedException('You do not have access to edit this mission.');
            }
        }

        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mission->setLastUpdate(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Mission updated successfully!');

            return $this->redirectToRoute('app_mission_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mission/edit.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }

    #[Route('/mission/{id}', name: 'app_mission_delete', methods: ['POST'])]
    public function missionDelete(Request $request, Mission $mission, EntityManagerInterface $entityManager): Response
    {
        $userRole = $request->getSession()->get('user_role');
        $userId = $request->getSession()->get('user_id');

        // Only allow delete if admin, creator, or in same group
        if ($userRole !== 'ROLE_ADMIN') {
            $currentUser = $userId ? $entityManager->getRepository(Users::class)->find($userId) : null;
            if (!$this->isCoworker($currentUser, $mission->getAssignedBy())) {
                throw $this->createAccessDeniedException('You do not have access to delete this mission.');
            }
        }

        if ($this->isCsrfTokenValid('delete' . $mission->getId(), $request->request->get('_token'))) {
            $entityManager->remove($mission);
            $entityManager->flush();
            $this->addFlash('success', 'Mission deleted successfully!');
        }

        return $this->redirectToRoute('app_mission_index', [], Response::HTTP_SEE_OTHER);
    }

    // --- TASK ---

    #[Route('/task', name: 'app_task_index', methods: ['GET'])]
    public function taskIndex(TaskRepository $taskRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $userRole = $request->getSession()->get('user_role');
        $userId = $request->getSession()->get('user_id');

        if ($userRole === 'ROLE_MEMBER' && $userId) {
            $currentUser = $entityManager->getRepository(Users::class)->find($userId);
            if ($currentUser && !$currentUser->getGroups()->isEmpty()) {
                $groupIds = $currentUser->getGroups()->map(fn($g) => $g->getId())->toArray();
                $tasks = $taskRepository->createQueryBuilder('t')
                    ->leftJoin('t.belongTo', 'm')
                    ->leftJoin('m.assignedBy', 'ma')
                    ->leftJoin('t.issuedBy', 'ib')
                    ->leftJoin('t.assumedBy', 'ab')
                    ->leftJoin('ma.groups', 'mag')
                    ->leftJoin('App\Entity\Group', 'mao', 'WITH', 'mao.owner = ma')
                    ->leftJoin('ib.groups', 'ibg')
                    ->leftJoin('App\Entity\Group', 'ibo', 'WITH', 'ibo.owner = ib')
                    ->leftJoin('ab.groups', 'abg')
                    ->leftJoin('App\Entity\Group', 'abo', 'WITH', 'abo.owner = ab')
                    ->where('mag.id IN (:groupIds) OR mao.id IN (:groupIds) OR ibg.id IN (:groupIds) OR ibo.id IN (:groupIds) OR abg.id IN (:groupIds) OR abo.id IN (:groupIds)')
                    ->setParameter('groupIds', $groupIds)
                    ->getQuery()
                    ->getResult();
            } else {
                $tasks = [];
            }
        } else {
            $tasks = $taskRepository->findAll();
        }

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    #[Route('/task/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function taskNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = new Task();

        $missionId = $request->query->get('mission_id');
        if ($missionId) {
            $mission = $entityManager->getRepository(Mission::class)->find($missionId);
            if ($mission) {
                $task->setBelongTo($mission);
            }
        }

        // Restore task form data from query parameters (when returning from mission creation)
        if ($request->query->has('title')) {
            $task->setTitle($request->query->get('title'));
        }
        if ($request->query->has('description')) {
            $task->setDescription($request->query->get('description'));
        }
        if ($request->query->has('t_date') && $request->query->has('t_time')) {
            try {
                $dateStr = $request->query->get('t_date') . ' ' . $request->query->get('t_time');
                $task->setTimeLimit(new \DateTime($dateStr));
            } catch (\Exception $e) {
                // Invalid date format, ignore
            }
        }
        if ($request->query->has('assumedBy')) {
            $assumedById = $request->query->get('assumedBy');
            $assumedBy = $entityManager->getRepository(Users::class)->find($assumedById);
            if ($assumedBy) {
                $task->setAssumedBy($assumedBy);
            }
        }

        $userRole = $request->getSession()->get('user_role');
        $userId = $request->getSession()->get('user_id');
        $currentUser = $userId ? $entityManager->getRepository(Users::class)->find($userId) : null;

        $groupIds = [];
        if ($currentUser) {
            $groupIds = $currentUser->getGroups()->map(fn($g) => $g->getId())->toArray();
            foreach ($entityManager->getRepository(Group::class)->findBy(['owner' => $currentUser]) as $ownedGroup) {
                $groupIds[] = $ownedGroup->getId();
            }
            $groupIds = array_unique($groupIds);
        }

        $form = $this->createForm(TaskType::class, $task, [
            'groupIds' => $groupIds,
            'isAdmin' => ($userRole === 'ROLE_ADMIN'),
            'currentUser' => $currentUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setCreatedAt(new \DateTimeImmutable());
            $task->setState('todo');

            $session = $request->getSession();
            $userId = $session->get('user_id');
            $user = $userId ? $entityManager->getRepository(Users::class)->find($userId) : null;
            if ($user) {
                $task->setIssuedBy($user);
            }

            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'Task created successfully!');

            return $this->redirectToRoute('app_task_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/new.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/task/{id}', name: 'app_task_show', methods: ['GET'])]
    public function taskShow(Task $task, Request $request, EntityManagerInterface $entityManager): Response
    {
        $userRole = $request->getSession()->get('user_role');
        $userId = $request->getSession()->get('user_id');

        if ($userRole === 'ROLE_MEMBER' && $userId) {
            $currentUser = $entityManager->getRepository(Users::class)->find($userId);
            $allowed = false;
            if ($currentUser) {
                $missionOwner = $task->getBelongTo() ? $task->getBelongTo()->getAssignedBy() : null;
                if (
                    $this->isCoworker($currentUser, $missionOwner) ||
                    $this->isCoworker($currentUser, $task->getIssuedBy()) ||
                    $this->isCoworker($currentUser, $task->getAssumedBy())
                ) {
                    $allowed = true;
                }
            }
            if (!$allowed) {
                throw $this->createAccessDeniedException('You do not have access to this task.');
            }
        }

        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/task/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function taskEdit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $userRole = $request->getSession()->get('user_role');
        $userId = $request->getSession()->get('user_id');

        if ($userRole === 'ROLE_MEMBER' && $userId) {
            $currentUser = $entityManager->getRepository(Users::class)->find($userId);
            $allowed = false;
            if ($currentUser) {
                $missionOwner = $task->getBelongTo() ? $task->getBelongTo()->getAssignedBy() : null;
                if (
                    $this->isCoworker($currentUser, $missionOwner) ||
                    $this->isCoworker($currentUser, $task->getIssuedBy()) ||
                    $this->isCoworker($currentUser, $task->getAssumedBy())
                ) {
                    $allowed = true;
                }
            }
            if (!$allowed) {
                throw $this->createAccessDeniedException('You do not have access to edit this task.');
            }
        }

        $groupIds = [];
        $currentUser = $userId ? $entityManager->getRepository(Users::class)->find($userId) : null;
        if ($currentUser) {
            $groupIds = $currentUser->getGroups()->map(fn($g) => $g->getId())->toArray();
            foreach ($entityManager->getRepository(Group::class)->findBy(['owner' => $currentUser]) as $ownedGroup) {
                $groupIds[] = $ownedGroup->getId();
            }
            $groupIds = array_unique($groupIds);
        }

        $form = $this->createForm(TaskType::class, $task, [
            'groupIds' => $groupIds,
            'isAdmin' => ($userRole === 'ROLE_ADMIN'),
            'currentUser' => $currentUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Task updated successfully!');

            return $this->redirectToRoute('app_task_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/task/{id}/update-state', name: 'app_task_update_state', methods: ['POST'])]
    public function updateState(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $userRole = $request->getSession()->get('user_role');
        $userId = $request->getSession()->get('user_id');

        if ($userRole === 'ROLE_MEMBER' && $userId) {
            $currentUser = $entityManager->getRepository(Users::class)->find($userId);
            $allowed = false;
            if ($currentUser) {
                $missionOwner = $task->getBelongTo() ? $task->getBelongTo()->getAssignedBy() : null;
                if (
                    $this->isCoworker($currentUser, $missionOwner) ||
                    $this->isCoworker($currentUser, $task->getIssuedBy()) ||
                    $this->isCoworker($currentUser, $task->getAssumedBy())
                ) {
                    $allowed = true;
                }
            }
            if (!$allowed) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to update this task'
                ], Response::HTTP_FORBIDDEN);
            }
        }
        $data = json_decode($request->getContent(), true);
        $newState = $data['state'] ?? null;

        // Prevent completed tasks from being moved back
        if ($task->getState() === 'completed') {
            return $this->json([
                'status' => 'error',
                'message' => 'Completed tasks cannot be moved to other states'
            ], Response::HTTP_FORBIDDEN);
        }

        $validStates = ['todo', 'in_progress', 'completed'];

        if ($newState && in_array($newState, $validStates)) {
            $task->setState($newState);

            // Set completedAt timestamp when task is marked as completed
            if ($newState === 'completed') {
                $task->setCompletedAt(new \DateTime());
            }

            $entityManager->flush();

            return $this->json(['status' => 'success', 'new_state' => $newState]);
        }

        return $this->json(['status' => 'error', 'message' => 'Invalid state'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/task/{id}', name: 'app_task_delete', methods: ['POST'])]
    public function taskDelete(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $entityManager->remove($task);
            $entityManager->flush();
            $this->addFlash('success', 'Task deleted successfully!');
        }

        return $this->redirectToRoute('app_task_index', [], Response::HTTP_SEE_OTHER);
    }

    private function isCoworker(?Users $userA, ?Users $userB): bool
    {
        if (!$userA || !$userB) {
            return false;
        }

        if ($userA->getId() === $userB->getId()) {
            return true;
        }

        foreach ($userA->getGroups() as $group) {
            if ($group->getMembers()->contains($userB) || ($group->getOwner() && $group->getOwner()->getId() === $userB->getId())) {
                return true;
            }
        }

        // Also check if userA is an owner of a group userB is in
        foreach ($userB->getGroups() as $group) {
            if ($group->getOwner() && $group->getOwner()->getId() === $userA->getId()) {
                return true;
            }
        }

        return false;
    }
}
