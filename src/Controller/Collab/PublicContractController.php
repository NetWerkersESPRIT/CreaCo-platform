<?php

namespace App\Controller\Collab;

use App\Entity\Contract;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/public/contract')]
class PublicContractController extends AbstractController
{
    #[Route('/{contractNumber}/sign/{token}', name: 'app_public_contract_signature_view', methods: ['GET'])]
    public function signatureView(string $contractNumber, string $token, ContractRepository $repo): Response
    {
        $contract = $repo->findOneBy([
            'contractNumber' => $contractNumber,
            'signatureToken' => $token
        ]);

        if (!$contract) {
            throw $this->createNotFoundException("Contract not found or invalid link.");
        }

        return $this->render('contract/public/signature_view.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{contractNumber}/sign/{token}/submit', name: 'app_public_contract_sign_submit', methods: ['POST'])]
    public function submitSignature(string $contractNumber, string $token, ContractRepository $repo, EntityManagerInterface $em): Response
    {
        $contract = $repo->findOneBy([
            'contractNumber' => $contractNumber,
            'signatureToken' => $token
        ]);

        if (!$contract) {
            throw $this->createNotFoundException("Contract not found or invalid link.");
        }

        if ($contract->isSignedByCollaborator()) {
            $this->addFlash('warning', 'Document already signed.');
            return $this->redirectToRoute('app_public_contract_signature_view', [
                'contractNumber' => $contractNumber,
                'token' => $token
            ]);
        }

        $contract->setSignedByCollaborator(true);
        $contract->setCollaboratorSignatureDate(new \DateTime());
        $contract->setStatus('SIGNED_BY_COLLABORATOR');

        $em->flush();

        $this->addFlash('success', 'Document signed successfully ! Thank you.');

        return $this->redirectToRoute('app_public_contract_signature_view', [
            'contractNumber' => $contractNumber,
            'token' => $token
        ]);
    }
}
