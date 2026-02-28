<?php

namespace App\Controller\Collab\Manager;

use App\Entity\Contract;
use App\Entity\Notification;
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
use App\Service\DocuSignService;

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

        $status = $request->query->get('status');
        $search = $request->query->get('search');

        $contracts = $repo->filterContracts($userId, $userRole, $status, $search);

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->render('manager/contract/_list.html.twig', [
                'contracts' => $contracts,
            ]);
        }

        return $this->render('manager/contract/index.html.twig', [
            'contracts' => $contracts,
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

    // ...

    #[Route('/{id}/send', name: 'app_manager_contract_send', methods: ['POST'])]
    public function send(Contract $contract, EntityManagerInterface $em, Request $request, DocuSignService $docuSignService): Response
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

        // Generate absolute URL for return
        $returnUrl = $this->generateUrl('app_public_contract_signature_view', [
            'contractNumber' => $contract->getContractNumber(),
            'token' => $contract->getSignatureToken()
        ], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            // Attempt to send via DocuSign
            $envelopeId = $docuSignService->sendContractToCollaborator(
                $contract->getCollaborator()->getEmail(),
                $contract->getCollaborator()->getName(),
                $contract->getTitle(),
                $contract->getTerms(),
                $contract->getContractNumber(),
                $returnUrl
            );

            // Successfully sent
            $contract->setStatus('SENT_TO_COLLABORATOR');
            $contract->setSentAt(new \DateTime());
            $contract->setDocusignEnvelopeId($envelopeId);
            $em->flush();

            // Notify Creator
            $creator = $contract->getCreator();
            if ($creator) {
                $notification = new Notification();
                $notification->setMessage("A contract for '" . $contract->getTitle() . "' has been sent to the partner for signature.");
                $notification->setUserId($creator);
                $notification->setIsRead(false);
                $notification->setCreatedAt(new \DateTime());
                $notification->setType('contract_sent');
                $notification->setRelatedId($contract->getId());
                $notification->setTargetUrl($this->generateUrl('app_contract_show', ['id' => $contract->getId()]));
                $em->persist($notification);
                $em->flush();
            }

            $this->addFlash('success', 'The contract has been sent successfully to the partner via DocuSign.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Failed to dispatch document via DocuSign: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_manager_contract_index');
    }
}
