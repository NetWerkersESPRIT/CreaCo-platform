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

// #[Route('/manager/collab-request')]
#[Route('/manager/collab-request')]
// Access allowed for MANAGERs OR (CONTENT_CREATORs without a manager)
#[IsGranted('ROLE_MANAGER', message: 'Access denied.')]
class ManagerCollabRequestController extends AbstractController
{
    #[Route('/', name: 'app_manager_collab_request_index', methods: ['GET'])]
    public function index(CollabRequestRepository $repo, EntityManagerInterface $em, #[CurrentUser] ?Users $user): Response
    {
        $user = $user ?? $em->getRepository(Users::class)->findOneBy([]);
        // Access allowed for MANAGERs OR (CONTENT_CREATORs without a manager)
        if (!$this->isGranted('ROLE_MANAGER') && ($user->getManager() !== null)) {
            throw $this->createAccessDeniedException("Access denied: You have a manager, so you cannot access this.");
        }

        // Liste toutes les demandes assignées au manager, PENDING en priorité
        $requests = $repo->findBy(['revisor' => $user], ['status' => 'ASC', 'createdAt' => 'DESC']);

        return $this->render('manager/collab_request/index.html.twig', [
            'collab_requests' => $requests,
        ]);
    }

    #[Route('/{id}', name: 'app_manager_collab_request_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(CollabRequest $collabRequest, EntityManagerInterface $em, #[CurrentUser] ?Users $user): Response
    {
        $user = $user ?? $em->getRepository(Users::class)->findOneBy([]);
        /*
        if ($user->getRole() !== 'manager' || $collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Accès refusé.");
        }
        */

        return $this->render('manager/collab_request/show.html.twig', [
            'collab_request' => $collabRequest,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_manager_collab_request_approve', methods: ['POST'])]
    public function approve(CollabRequest $collabRequest, EntityManagerInterface $em, #[CurrentUser] ?Users $user): Response
    {
        $user = $user ?? $em->getRepository(Users::class)->findOneBy([]);
        /*
        if ($user->getRole() !== 'manager' || $collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Accès refusé.");
        }
        */

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

        $this->addFlash('success', 'La demande a été approuvée et un brouillon de contrat a été généré.');

        return $this->redirectToRoute('app_manager_collab_request_index');
    }

    #[Route('/{id}/reject', name: 'app_manager_collab_request_reject', methods: ['GET', 'POST'])]
    public function reject(CollabRequest $collabRequest, Request $request, EntityManagerInterface $em, #[CurrentUser] ?Users $user): Response
    {
        $user = $user ?? $em->getRepository(Users::class)->findOneBy([]);
        /*
        if ($user->getRole() !== 'manager' || $collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Accès refusé.");
        }
        */

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

            $this->addFlash('danger', 'La demande de collaboration a été rejetée.');

            return $this->redirectToRoute('app_manager_collab_request_index');
        }

        return $this->render('manager/collab_request/reject.html.twig', [
            'collab_request' => $collabRequest,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/request-modification', name: 'app_manager_collab_request_modify', methods: ['GET', 'POST'])]
    public function requestModification(CollabRequest $collabRequest, Request $request, EntityManagerInterface $em, #[CurrentUser] ?Users $user): Response
    {
        $user = $user ?? $em->getRepository(Users::class)->findOneBy([]);
        /*
        if ($user->getRole() !== 'manager' || $collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Accès refusé.");
        }
        */

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
            $collabRequest->setRejectionReason($comment); // Utilisation du même champ pour stocker le commentaire
            $collabRequest->setRespondedAt(new \DateTime());

            $em->flush();

            $this->addFlash('info', 'Une demande de modification a été envoyée au créateur.');

            return $this->redirectToRoute('app_manager_collab_request_index');
        }

        return $this->render('manager/collab_request/request_modification.html.twig', [
            'collab_request' => $collabRequest,
            'form' => $form->createView(),
        ]);
    }
}
