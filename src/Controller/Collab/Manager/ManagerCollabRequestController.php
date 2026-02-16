<?php

namespace App\Controller\Collab\Manager;

use App\Entity\CollabRequest;
use App\Entity\Contract;
use App\Entity\Users;
use App\Form\RejectionReasonType;
use App\Repository\CollabRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/manager/collab-request')]
class ManagerCollabRequestController extends AbstractController
{
    #[Route('/', name: 'app_manager_collab_request_index', methods: ['GET'])]
    public function index(CollabRequestRepository $repo, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $userRole = $session->get('user_role');
        $userId = $session->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('app_auth');
        }

        if ($userRole !== 'ROLE_MANAGER') {
            throw $this->createAccessDeniedException("Accès réservé aux managers.");
        }

        $user = $em->getRepository(Users::class)->find($userId);

        // Liste toutes les demandes assignées au manager, PENDING en priorité
        $requests = $repo->findBy(['revisor' => $user], ['status' => 'ASC', 'createdAt' => 'DESC']);

        return $this->render('manager/collab_request/index.html.twig', [
            'collab_requests' => $requests,
        ]);
    }

    #[Route('/{id}', name: 'app_manager_collab_request_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(CollabRequest $collabRequest, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $userRole = $session->get('user_role');
        $userId = $session->get('user_id');

        if (!$userId) {
            return $this->redirectToRoute('app_auth');
        }

        if ($userRole !== 'ROLE_MANAGER') {
            throw $this->createAccessDeniedException("Accès réservé aux managers.");
        }

        $user = $em->getRepository(Users::class)->find($userId);
        if ($collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Cette demande ne vous est pas assignée.");
        }

        return $this->render('manager/collab_request/show.html.twig', [
            'collab_request' => $collabRequest,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_manager_collab_request_approve', methods: ['POST'])]
    public function approve(CollabRequest $collabRequest, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $userRole = $session->get('user_role');
        $userId = $session->get('user_id');

        if (!$userId || $userRole !== 'ROLE_MANAGER') {
            throw $this->createAccessDeniedException("Action non autorisée.");
        }

        $user = $em->getRepository(Users::class)->find($userId);
        if ($collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Cette demande ne vous est pas assignée.");
        }

        if ($collabRequest->getStatus() !== 'PENDING') {
            throw $this->createAccessDeniedException("Seules les demandes en attente peuvent être approuvées.");
        }

        $collabRequest->setStatus('APPROVED');
        $collabRequest->setRespondedAt(new \DateTime());

        // Génération automatique du contrat
        $contract = new Contract();
        $contract->setCollabRequest($collabRequest);
        $contract->setCreator($collabRequest->getCreator());
        $contract->setCollaborator($collabRequest->getCollaborator());
        $contract->setTitle('Contrat pour : ' . $collabRequest->getTitle());
        $contract->setStartDate($collabRequest->getStartDate());
        $contract->setEndDate($collabRequest->getEndDate());
        $contract->setAmount($collabRequest->getBudget() ?? '0');
        $contract->setTerms($collabRequest->getDeliverables());
        $contract->setPaymentSchedule($collabRequest->getPaymentTerms());
        $contract->setStatus('DRAFT');

        $em->persist($contract);
        $em->flush();

        $this->addFlash('success', 'The request has been approved and a draft contract has been generated.');

        return $this->redirectToRoute('app_manager_collab_request_index');
    }

    #[Route('/{id}/reject', name: 'app_manager_collab_request_reject', methods: ['GET', 'POST'])]
    public function reject(CollabRequest $collabRequest, Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $userRole = $session->get('user_role');
        $userId = $session->get('user_id');

        if (!$userId || $userRole !== 'ROLE_MANAGER') {
            throw $this->createAccessDeniedException("Action non autorisée.");
        }

        $user = $em->getRepository(Users::class)->find($userId);
        if ($collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Cette demande ne vous est pas assignée.");
        }

        if ($collabRequest->getStatus() !== 'PENDING') {
            throw $this->createAccessDeniedException("Seules les demandes en attente peuvent être rejetées.");
        }

        $form = $this->createForm(RejectionReasonType::class, null, [
            'attr' => ['novalidate' => 'novalidate']
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reason = $form->get('rejectionReason')->getData();

            $collabRequest->setStatus('REJECTED');
            $collabRequest->setRejectionReason($reason);
            $collabRequest->setRespondedAt(new \DateTime());

            $em->flush();

            $this->addFlash('danger', 'The collaboration request has been rejected.');

            return $this->redirectToRoute('app_manager_collab_request_index');
        }

        return $this->render('manager/collab_request/reject.html.twig', [
            'collab_request' => $collabRequest,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/request-modification', name: 'app_manager_collab_request_modify', methods: ['GET', 'POST'])]
    public function requestModification(CollabRequest $collabRequest, Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $userRole = $session->get('user_role');
        $userId = $session->get('user_id');

        if (!$userId || $userRole !== 'ROLE_MANAGER') {
            throw $this->createAccessDeniedException("Action non autorisée.");
        }

        $user = $em->getRepository(Users::class)->find($userId);
        if ($collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Cette demande ne vous est pas assignée.");
        }

        if ($collabRequest->getStatus() !== 'PENDING') {
            throw $this->createAccessDeniedException("Seul un statut PENDING autorise une demande de modification.");
        }

        $form = $this->createForm(RejectionReasonType::class, null, [
            'attr' => ['novalidate' => 'novalidate']
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->get('rejectionReason')->getData();

            $collabRequest->setStatus('MODIFICATION_REQUESTED');
            $collabRequest->setRejectionReason($comment);
            $collabRequest->setRespondedAt(new \DateTime());

            $em->flush();

            $this->addFlash('info', 'A modification request has been sent to the creator.');

            return $this->redirectToRoute('app_manager_collab_request_index');
        }

        return $this->render('manager/collab_request/request_modification.html.twig', [
            'collab_request' => $collabRequest,
            'form' => $form->createView(),
        ]);
    }
}
