<?php

namespace App\Controller;

use App\Entity\Idea;
use App\Entity\Users;
use App\Form\IdeaType;
use App\Repository\IdeaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IdeaController extends AbstractController
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
        if ($this->isCsrfTokenValid('delete' . $idea->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($idea);
            $entityManager->flush();
            $this->addFlash('success', 'Idea deleted successfully!');
        }

        return $this->redirectToRoute('app_idea_index', [], Response::HTTP_SEE_OTHER);
    }
}
