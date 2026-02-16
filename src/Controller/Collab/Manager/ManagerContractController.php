<?php

namespace App\Controller\Collab\Manager;

use App\Entity\Contract;
use App\Entity\Users;
use App\Form\ContractType;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/manager/contract')]
class ManagerContractController extends AbstractController
{
    #[Route('/', name: 'app_manager_contract_index', methods: ['GET'])]
    public function index(ContractRepository $repo, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $userRole = $session->get('user_role');
        $userId = $session->get('user_id');

        if (!$userId || $userRole !== 'ROLE_MANAGER') {
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('manager/contract/index.html.twig', [
            'contracts' => $repo->findForRevisor($userId),
        ]);
    }

    #[Route('/{id}', name: 'app_manager_contract_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Contract $contract, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $userRole = $session->get('user_role');
        $userId = $session->get('user_id');

        if (!$userId || $userRole !== 'ROLE_MANAGER') {
            return $this->redirectToRoute('app_auth');
        }

        $user = $em->getRepository(Users::class)->find($userId);
        $collabRequest = $contract->getCollabRequest();
        if (!$collabRequest || $collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce contrat.");
        }

        return $this->render('manager/contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_manager_contract_edit', methods: ['GET', 'POST'])]
    public function edit(Contract $contract, Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $userRole = $session->get('user_role');
        $userId = $session->get('user_id');

        if (!$userId || $userRole !== 'ROLE_MANAGER') {
            return $this->redirectToRoute('app_auth');
        }

        $user = $em->getRepository(Users::class)->find($userId);
        $collabRequest = $contract->getCollabRequest();
        if (!$collabRequest || $collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce contrat.");
        }

        $form = $this->createForm(ContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Contract updated.');
            return $this->redirectToRoute('app_manager_contract_index');
        }

        return $this->render('manager/contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/send', name: 'app_manager_contract_send', methods: ['POST'])]
    public function send(Contract $contract, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $userRole = $session->get('user_role');
        $userId = $session->get('user_id');

        if (!$userId || $userRole !== 'ROLE_MANAGER') {
            return $this->redirectToRoute('app_auth');
        }

        $user = $em->getRepository(Users::class)->find($userId);
        $collabRequest = $contract->getCollabRequest();
        if (!$collabRequest || $collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce contrat.");
        }

        if ($contract->getStatus() !== 'DRAFT') {
            $this->addFlash('warning', 'This contract has already been sent.');
            return $this->redirectToRoute('app_manager_contract_index');
        }

        $contract->setStatus('SENT_TO_COLLABORATOR');
        $contract->setSentAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'The contract has been sent for signature.');
        return $this->redirectToRoute('app_manager_contract_index');
    }
}
