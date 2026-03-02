<?php

namespace App\Controller\Collab\Admin;

use App\Entity\Contract;
use App\Entity\Users;
use App\Repository\ContractRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\DocuSignService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/admin/collab/ledger')]
class PartnershipLedgerController extends AbstractController
{
    #[Route('/', name: 'admin_collab_ledger_index', methods: ['GET'])]
    public function index(Request $request, ContractRepository $contractRepo, UsersRepository $userRepo): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            $this->addFlash('warning', 'Access restricted to administrators.');
            return $this->redirectToRoute('app_auth');
        }

        $filters = [
            'status' => $request->query->get('status'),
            'manager' => $request->query->get('manager'),
            'budget_min' => $request->query->get('budget_min'),
            'budget_max' => $request->query->get('budget_max'),
            'signature_state' => $request->query->get('signature_state'),
            'risk_level' => $request->query->get('risk_level'), // Based on AI score
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
        ];

        $contracts = $this->getFilteredContracts($contractRepo, $filters);
        $managers = $userRepo->findBy(['role' => 'ROLE_MANAGER']);

        return $this->render('back/collab/partnership_ledger.html.twig', [
            'contracts' => $contracts,
            'managers' => $managers,
            'filters' => $filters,
        ]);
    }

    private function getFilteredContracts(ContractRepository $repo, array $filters): array
    {
        $qb = $repo->createQueryBuilder('c')
            ->leftJoin('c.collabRequest', 'r')
            ->leftJoin('c.collaborator', 'collab')
            ->leftJoin('c.creator', 'creator')
            ->leftJoin('r.revisor', 'manager');

        if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
            $qb->andWhere('c.status = :status')->setParameter('status', $filters['status']);
        }

        if (!empty($filters['manager'])) {
            $qb->andWhere('manager.id = :managerId')->setParameter('managerId', $filters['manager']);
        }

        if (!empty($filters['budget_min'])) {
            $qb->andWhere('c.amount >= :budgetMin')->setParameter('budgetMin', $filters['budget_min']);
        }

        if (!empty($filters['budget_max'])) {
            $qb->andWhere('c.amount <= :budgetMax')->setParameter('budgetMax', $filters['budget_max']);
        }

        if (!empty($filters['signature_state'])) {
            if ($filters['signature_state'] === 'FULLY_SIGNED') {
                $qb->andWhere('c.signedByCreator = true AND c.signedByCollaborator = true');
            } elseif ($filters['signature_state'] === 'PENDING_CREATOR') {
                $qb->andWhere('c.signedByCreator = false');
            } elseif ($filters['signature_state'] === 'PENDING_COLLABORATOR') {
                $qb->andWhere('c.signedByCollaborator = false');
            }
        }

        if (!empty($filters['risk_level'])) {
            // High Risk: AI Score < 40, Medium: 40-70, Low: > 70
            if ($filters['risk_level'] === 'HIGH') {
                $qb->andWhere('r.aiSuccessScore < 40');
            } elseif ($filters['risk_level'] === 'MEDIUM') {
                $qb->andWhere('r.aiSuccessScore >= 40 AND r.aiSuccessScore < 70');
            } elseif ($filters['risk_level'] === 'LOW') {
                $qb->andWhere('r.aiSuccessScore >= 70');
            }
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('c.startDate >= :dateFrom')->setParameter('dateFrom', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('c.endDate <= :dateTo')->setParameter('dateTo', $filters['date_to']);
        }

        return $qb->orderBy('c.createdAt', 'DESC')->getQuery()->getResult();
    }

    #[Route('/{id}/force-transition', name: 'admin_collab_ledger_force_transition', methods: ['POST'])]
    public function forceTransition(Contract $contract, Request $request, EntityManagerInterface $em): Response
    {
        $newStatus = $request->request->get('status');
        if (in_array($newStatus, ['DRAFT', 'SENT_TO_COLLABORATOR', 'ACTIVE', 'ARCHIVED', 'COMPLETED'])) {
            $contract->setStatus($newStatus);
            $em->flush();
            $this->addFlash('success', 'Status forced to ' . $newStatus);
        }
        return $this->redirectToRoute('admin_collab_ledger_index');
    }

    #[Route('/{id}/reassign-manager', name: 'admin_collab_ledger_reassign', methods: ['POST'])]
    public function reassignManager(Contract $contract, Request $request, EntityManagerInterface $em, UsersRepository $userRepo): Response
    {
        $managerId = $request->request->get('manager_id');
        $manager = $userRepo->find($managerId);
        if ($manager && $contract->getCollabRequest()) {
            $contract->getCollabRequest()->setRevisor($manager);
            $em->flush();
            $this->addFlash('success', 'Manager reassigned successfully.');
        }
        return $this->redirectToRoute('admin_collab_ledger_index');
    }

    #[Route('/{id}/retrigger-docusign', name: 'admin_collab_ledger_retrigger_docusign', methods: ['POST'])]
    public function retriggerDocuSign(Contract $contract, DocuSignService $docuSignService, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): Response
    {
        try {
            $returnUrl = $urlGenerator->generate('app_public_contract_signature_view', [
                'contractNumber' => $contract->getContractNumber(),
                'token' => $contract->getSignatureToken()
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $envelopeId = $docuSignService->sendContractToCollaborator(
                $contract->getCollaborator()->getEmail(),
                $contract->getCollaborator()->getName(),
                $contract->getTitle(),
                $contract->getTerms(),
                $contract->getContractNumber(),
                $returnUrl
            );

            $contract->setDocusignEnvelopeId($envelopeId);
            $contract->setStatus('SENT_TO_COLLABORATOR');
            $contract->setSentAt(new \DateTime());
            $em->flush();
            $this->addFlash('success', 'DocuSign re-triggered. New Envelope ID: ' . $envelopeId);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'DocuSign Error: ' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_collab_ledger_index');
    }

    #[Route('/{id}/archive', name: 'admin_collab_ledger_archive', methods: ['POST'])]
    public function archive(Contract $contract, EntityManagerInterface $em): Response
    {
        $contract->setStatus('ARCHIVED');
        $em->flush();
        $this->addFlash('success', 'Contract moved to archives.');
        return $this->redirectToRoute('admin_collab_ledger_index');
    }
}
