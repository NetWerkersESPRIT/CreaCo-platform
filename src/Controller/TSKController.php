<?php

namespace App\Controller;

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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TSKController extends AbstractController
{

    #[Route('/idea', name: 'app_idea_index', methods: ['GET'])]
    public function ideaIndex(IdeaRepository $ideaRepository): Response
    {
        return $this->render('tsk/index.html.twig', [
            'ideas' => $ideaRepository->findAll(),
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

            $creator = $entityManager->getRepository(Users::class)->find(1);
            if ($creator) {
                $idea->setCreator($creator);
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
    public function missionIndex(MissionRepository $missionRepository): Response
    {
        $missions = $missionRepository->findAll();

        $calendarData = [];
        foreach ($missions as $mission) {
            $calendarData[] = [
                'id' => $mission->getId(),
                'title' => $mission->getTitle(),
                'start' => $mission->getMissionDate() ? $mission->getMissionDate()->format('Y-m-d') : $mission->getCreatedAt()->format('Y-m-d'),
                'url' => $this->generateUrl('app_mission_show', ['id' => $mission->getId()]),
                'backgroundColor' => $mission->getState() === 'completed' ? '#10b981' : ($mission->getState() === 'in_progress' ? '#3b82f6' : '#9333ea'),
                'borderColor' => $mission->getState() === 'completed' ? '#10b981' : ($mission->getState() === 'in_progress' ? '#3b82f6' : '#9333ea'),
                'description' => $mission->getDescription(),
                'state' => $mission->getState(),
                'idea' => $mission->getImplementIdea() ? $mission->getImplementIdea()->getTitle() : 'N/A',
            ];
        }

        return $this->render('mission/index.html.twig', [
            'missions' => $missions,
            'calendar_data' => json_encode($calendarData),
        ]);
    }

    #[Route('/mission/new', name: 'app_mission_new', methods: ['GET', 'POST'])]
    public function missionNew(Request $request, EntityManagerInterface $entityManager): Response
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

            $idea = $mission->getImplementIdea();
            if ($idea && $idea->getCreator()) {
                $mission->setAssignedBy($idea->getCreator());
            }

            $entityManager->persist($mission);
            $entityManager->flush();

            $this->addFlash('success', 'Mission created successfully!');

            return $this->redirectToRoute('app_mission_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mission/new.html.twig', [
            'mission' => $mission,
            'form' => $form,
        ]);
    }

    #[Route('/mission/{id}', name: 'app_mission_show', methods: ['GET'])]
    public function missionShow(Mission $mission): Response
    {
        return $this->render('mission/show.html.twig', [
            'mission' => $mission,
        ]);
    }

    #[Route('/mission/{id}/edit', name: 'app_mission_edit', methods: ['GET', 'POST'])]
    public function missionEdit(Request $request, Mission $mission, EntityManagerInterface $entityManager): Response
    {
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
        if ($this->isCsrfTokenValid('delete' . $mission->getId(), $request->request->get('_token'))) {
            $entityManager->remove($mission);
            $entityManager->flush();
            $this->addFlash('success', 'Mission deleted successfully!');
        }

        return $this->redirectToRoute('app_mission_index', [], Response::HTTP_SEE_OTHER);
    }

    // --- TASK ---

    #[Route('/task', name: 'app_task_index', methods: ['GET'])]
    public function taskIndex(TaskRepository $taskRepository): Response
    {
        return $this->render('task/index.html.twig', [
            'tasks' => $taskRepository->findAll(),
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

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setCratedAt(new \DateTimeImmutable());

            $issuer = $entityManager->getRepository(Users::class)->find(1);
            if ($issuer) {
                $task->setIssuedBy($issuer);
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
    public function taskShow(Task $task): Response
    {
        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/task/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function taskEdit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TaskType::class, $task);
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
}
