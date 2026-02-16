<?php

namespace App\Controller\Collab;

use App\Entity\CollabRequest;
use App\Entity\Collaborator;
use App\Entity\Users;
use App\Form\CollabRequestType;
use App\Repository\CollabRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/collab-request')]
class CollabRequestController extends AbstractController
{
    #[Route('/', name: 'app_collab_request_index', methods: ['GET'])]
    public function index(CollabRequestRepository $repo, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(Users::class)->find($userId) : null;

        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        if ($user->getRole() === 'ROLE_MANAGER') {
            throw $this->createAccessDeniedException("Accès refusé aux managers sur cet espace.");
        }

        return $this->render('collab_request/index.html.twig', [
            'requests' => $repo->findBy(['creator' => $user], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new/{id?}', name: 'app_collab_request_new', methods: ['GET', 'POST'])]
    public function new(?Collaborator $collaborator, Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(Users::class)->find($userId) : null;

        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        if ($user->getRole() === 'ROLE_MANAGER') {
            throw $this->createAccessDeniedException("Accès refusé aux managers sur cet espace.");
        }

        $collabRequest = new CollabRequest();
        if ($collaborator) {
            $collabRequest->setCollaborator($collaborator);
        }
        $collabRequest->setCreator($user);
        $collabRequest->setStatus('PENDING');

        $form = $this->createForm(CollabRequestType::class, $collabRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user) {
                $collabRequest->setCreator($user);
            }
            $em->persist($collabRequest);
            $em->flush();

            $this->addFlash('success', 'Collaboration request sent successfully.');

            return $this->redirectToRoute('app_collab_request_index');
        }

        return $this->render('collab_request/new.html.twig', [
            'collab_request' => $collabRequest,
            'collaborator' => $collaborator,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_collab_request_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(CollabRequest $collabRequest, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(Users::class)->find($userId) : null;

        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        if ($user->getRole() === 'ROLE_MANAGER') {
            throw $this->createAccessDeniedException("Accès refusé aux managers sur cet espace.");
        }

        /*
        if ($collabRequest->getCreator() !== $user) {
            throw $this->createAccessDeniedException("Vous n'êtes pas l'auteur de cette demande.");
        }
        */

        return $this->render('collab_request/show.html.twig', [
            'collab_request' => $collabRequest,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_collab_request_edit', methods: ['GET', 'POST'])]
    public function edit(CollabRequest $collabRequest, Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(Users::class)->find($userId) : null;

        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        if ($user->getRole() === 'ROLE_MANAGER') {
            throw $this->createAccessDeniedException("Accès refusé aux managers sur cet espace.");
        }

        /*
        if ($collabRequest->getCreator() !== $user) {
            throw $this->createAccessDeniedException("Seul l'auteur peut modifier cette demande.");
        }
        */

        if ($collabRequest->getStatus() !== 'PENDING') {
            throw $this->createAccessDeniedException("Seules les demandes en attente (PENDING) peuvent être modifiées.");
        }

        $form = $this->createForm(CollabRequestType::class, $collabRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Collaboration request updated.');

            return $this->redirectToRoute('app_collab_request_index');
        }

        return $this->render('collab_request/edit.html.twig', [
            'collab_request' => $collabRequest,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_collab_request_cancel', methods: ['POST'])]
    public function cancel(CollabRequest $collabRequest, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(Users::class)->find($userId) : null;

        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        if ($user->getRole() === 'ROLE_MANAGER') {
            throw $this->createAccessDeniedException("Accès refusé aux managers sur cet espace.");
        }

        /*
        if ($collabRequest->getCreator() !== $user) {
            throw $this->createAccessDeniedException("Seul l'auteur peut annuler cette demande.");
        }
        */

        if ($collabRequest->getStatus() !== 'PENDING') {
            throw $this->createAccessDeniedException("Seules les demandes en attente (PENDING) peuvent être annulées.");
        }

        $collabRequest->setStatus('CANCELLED');
        $em->flush();

        $this->addFlash('warning', 'Collaboration request canceled.');

        return $this->redirectToRoute('app_collab_request_index');
    }
}
