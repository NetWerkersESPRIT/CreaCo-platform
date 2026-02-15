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
        return $this->redirectToRoute('admin_collab_collaborators');
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
            'collaborators' => $repo->findAll(),
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
            'requests' => $repo->findAll(),
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
            'contracts' => $repo->findAll(),
        ]);
    }

    #[Route('/collaborator/delete/{id}', name: 'admin_collab_collaborator_delete', methods: ['POST'])]
    public function deleteCollaborator(Request $request, Collaborator $collaborator, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_auth');
        }

        if ($this->isCsrfTokenValid('delete' . $collaborator->getId(), $request->request->get('_token'))) {
            $em->remove($collaborator);
            $em->flush();
            $this->addFlash('success', 'Collaborator deleted successfully.');
        }

        return $this->redirectToRoute('admin_collab_collaborators');
    }

    #[Route('/request/delete/{id}', name: 'admin_collab_request_delete', methods: ['POST'])]
    public function deleteRequest(Request $request, CollabRequest $collabRequest, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_auth');
        }

        if ($this->isCsrfTokenValid('delete' . $collabRequest->getId(), $request->request->get('_token'))) {
            $em->remove($collabRequest);
            $em->flush();
            $this->addFlash('success', 'Collaboration request deleted successfully.');
        }

        return $this->redirectToRoute('admin_collab_requests');
    }

    #[Route('/contract/delete/{id}', name: 'admin_collab_contract_delete', methods: ['POST'])]
    public function deleteContract(Request $request, Contract $contract, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_auth');
        }

        if ($this->isCsrfTokenValid('delete' . $contract->getId(), $request->request->get('_token'))) {
            $em->remove($contract);
            $em->flush();
            $this->addFlash('success', 'Contract deleted successfully.');
        }

        return $this->redirectToRoute('admin_collab_contracts');
    }
}
