<?php

namespace App\Controller;

use App\Entity\Collaborator;
use App\Entity\CollabRequest;
use App\Entity\Contract;
use App\Repository\CollaboratorRepository;
use App\Repository\CollabRequestRepository;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/collab')]
final class CollabAdminController extends AbstractController
{
    #[Route('', name: 'admin_collab_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_collab_dashboard');
    }

    #[Route('/dashboard', name: 'admin_collab_dashboard')]
    public function dashboard(
        Request $request,
        CollaboratorRepository $collabRepo,
        ContractRepository $contractRepo,
        CollabRequestRepository $requestRepo
    ): Response {
        if (!$this->isAdmin($request)) {
            $this->addFlash('warning', 'Access restricted to administrators.');
            return $this->redirectToRoute('app_auth');
        }

        $stats = [
            'partners' => [
                'total' => $collabRepo->count([]),
                'active' => $collabRepo->countByStatus('ACTIVE'),
                'inactive' => $collabRepo->countByStatus('INACTIVE'),
            ],
            'contracts' => [
                'total' => $contractRepo->count([]),
                'signed' => $contractRepo->countByStatus('SIGNED'),
                'pending' => $contractRepo->countByStatus('PENDING'),
                'expired' => $contractRepo->countByStatus('EXPIRED'),
            ],
            'requests' => [
                'total' => $requestRepo->count([]),
                'pending' => $requestRepo->countByStatus('PENDING'),
                'approved' => $requestRepo->countByStatus('APPROVED'),
                'rejected' => $requestRepo->countByStatus('REJECTED'),
                'revision' => $requestRepo->countByStatus('REVISION'),
            ],
            'revenue' => $contractRepo->getTotalRevenueSecured(),
            'avg_time' => $contractRepo->getAverageSignatureTime(),
            'velocity' => $contractRepo->getSignatureVelocityComparison(),
        ];

        return $this->render('back/collab/dashboard.html.twig', [
            'stats' => $stats,
            'recent_partners' => $collabRepo->findBy([], ['createdAt' => 'DESC'], 5),
            'recent_contracts' => $contractRepo->findBy([], ['createdAt' => 'DESC'], 5),
            'requests_to_review' => $requestRepo->findBy(['status' => 'PENDING'], ['createdAt' => 'DESC'], 5),
        ]);
    }

    private function isAdmin(Request $request): bool
    {
        return $request->getSession()->get('user_role') === 'ROLE_ADMIN';
    }

    #[Route('/collaborators', name: 'admin_collab_collaborators')]
    public function indexCollaborators(Request $request, CollaboratorRepository $repo): Response
    {
        if (!$this->isAdmin($request)) {
            $this->addFlash('warning', 'Access restricted to administrators.');
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('back/collab/collaborators.html.twig', [
            'collaborators' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/collaborator/new', name: 'admin_collab_collaborator_new')]
    public function newCollaborator(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) return $this->redirectToRoute('app_auth');

        $collaborator = new \App\Entity\Collaborator();
        $form = $this->createForm(\App\Form\CollaboratorType::class, $collaborator);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($collaborator);
            $em->flush();
            $this->addFlash('success', 'Partner registered successfully.');
            return $this->redirectToRoute('admin_collab_collaborators');
        }

        return $this->render('back/collab/collaborator_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Register New Partner',
        ]);
    }

    #[Route('/collaborator/edit/{id}', name: 'admin_collab_collaborator_edit')]
    public function editCollaborator(Request $request, \App\Entity\Collaborator $collaborator, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) return $this->redirectToRoute('app_auth');

        $form = $this->createForm(\App\Form\CollaboratorType::class, $collaborator);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Partner info updated successfully.');
            return $this->redirectToRoute('admin_collab_collaborators');
        }

        return $this->render('back/collab/collaborator_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edit Partner Info',
        ]);
    }

    #[Route('/collaborator/toggle/{id}', name: 'admin_collab_collaborator_toggle', methods: ['POST'])]
    public function toggleStatusCollaborator(Request $request, \App\Entity\Collaborator $collaborator, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) return $this->redirectToRoute('app_auth');

        $newStatus = $collaborator->getStatus() === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $collaborator->setStatus($newStatus);
        $em->flush();

        $this->addFlash('success', "Partner is now {$newStatus}.");
        return $this->redirectToRoute('admin_collab_collaborators');
    }

    #[Route('/collaborator/show/{id}', name: 'admin_collab_collaborator_show')]
    public function showCollaborator(Request $request, \App\Entity\Collaborator $collaborator): Response
    {
        if (!$this->isAdmin($request)) return $this->redirectToRoute('app_auth');

        return $this->render('back/collab/collaborator_show.html.twig', [
            'partner' => $collaborator,
        ]);
    }

    #[Route('/requests', name: 'admin_collab_requests')]
    public function indexRequests(Request $request, CollabRequestRepository $repo): Response
    {
        if (!$this->isAdmin($request)) {
            $this->addFlash('warning', 'Access restricted to administrators.');
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('back/collab/requests.html.twig', [
            'requests' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/request/show/{id}', name: 'admin_collab_request_show')]
    public function showRequest(Request $request, \App\Entity\CollabRequest $collabRequest, ContractRepository $contractRepo): Response
    {
        if (!$this->isAdmin($request)) return $this->redirectToRoute('app_auth');

        $contract = $contractRepo->findOneBy(['collabRequest' => $collabRequest]);

        return $this->render('back/collab/request_show.html.twig', [
            'request' => $collabRequest,
            'contract' => $contract,
        ]);
    }

    #[Route('/contracts', name: 'admin_collab_contracts')]
    public function indexContracts(Request $request, ContractRepository $repo): Response
    {
        if (!$this->isAdmin($request)) {
            $this->addFlash('warning', 'Access restricted to administrators.');
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('back/collab/contracts.html.twig', [
            'contracts' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/contract/show/{id}', name: 'admin_collab_contract_show')]
    public function showContract(Request $request, \App\Entity\Contract $contract): Response
    {
        if (!$this->isAdmin($request)) return $this->redirectToRoute('app_auth');

        return $this->render('back/collab/contract_show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/contract/expire/{id}', name: 'admin_collab_contract_expire', methods: ['POST'])]
    public function expireContract(Request $request, \App\Entity\Contract $contract, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) return $this->redirectToRoute('app_auth');

        $contract->setStatus('EXPIRED');
        $em->flush();

        $this->addFlash('success', 'Contract marked as expired.');
        return $this->redirectToRoute('admin_collab_contracts');
    }

    #[Route('/contract/cancel/{id}', name: 'admin_collab_contract_cancel', methods: ['POST'])]
    public function cancelContract(Request $request, \App\Entity\Contract $contract, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) return $this->redirectToRoute('app_auth');

        $contract->setStatus('CANCELLED');
        $em->flush();

        $this->addFlash('success', 'Contract cancelled.');
        return $this->redirectToRoute('admin_collab_contracts');
    }

    #[Route('/collaborator/delete/{id}', name: 'admin_collab_collaborator_delete', methods: ['POST'])]
    public function deleteCollaborator(Request $request, \App\Entity\Collaborator $collaborator, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) return $this->redirectToRoute('app_auth');

        if ($this->isCsrfTokenValid('delete' . $collaborator->getId(), (string)$request->request->get('_token'))) {
            $em->remove($collaborator);
            $em->flush();
            $this->addFlash('success', 'Collaborator deleted successfully.');
        }

        return $this->redirectToRoute('admin_collab_collaborators');
    }

    #[Route('/request/delete/{id}', name: 'admin_collab_request_delete', methods: ['POST'])]
    public function deleteRequest(Request $request, \App\Entity\CollabRequest $collabRequest, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) return $this->redirectToRoute('app_auth');

        if ($this->isCsrfTokenValid('delete' . $collabRequest->getId(), (string)$request->request->get('_token'))) {
            $em->remove($collabRequest);
            $em->flush();
            $this->addFlash('success', 'Collaboration request deleted successfully.');
        }

        return $this->redirectToRoute('admin_collab_requests');
    }

    #[Route('/contract/delete/{id}', name: 'admin_collab_contract_delete', methods: ['POST'])]
    public function deleteContract(Request $request, \App\Entity\Contract $contract, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) return $this->redirectToRoute('app_auth');

        if ($this->isCsrfTokenValid('delete' . $contract->getId(), (string)$request->request->get('_token'))) {
            $em->remove($contract);
            $em->flush();
            $this->addFlash('success', 'Contract deleted successfully.');
        }

        return $this->redirectToRoute('admin_collab_contracts');
    }
}
