<?php

namespace App\Controller\Collab;

use App\Entity\Contract;
use App\Entity\Users;
use App\Form\RejectionReasonType;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contract')]
// Access fully open (authentication removed)
class ContractController extends AbstractController
{
    #[Route('/', name: 'app_contract_index', methods: ['GET'])]
    public function index(ContractRepository $repo, EntityManagerInterface $em, Request $request): Response
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

        return $this->render('contract/index.html.twig', [
            'contracts' => $repo->findBy(['creator' => $user], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}', name: 'app_contract_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Contract $contract, EntityManagerInterface $em, Request $request): Response
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
        if ($contract->getCreator() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce contrat.");
        }
        */

        return $this->render('contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/download', name: 'app_contract_download', methods: ['GET'])]
    public function download(Contract $contract, EntityManagerInterface $em, Request $request): BinaryFileResponse
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        $user = $userId ? $em->getRepository(Users::class)->find($userId) : null;

        if (!$user) {
            throw $this->createAccessDeniedException("Non authentifié.");
        }

        if ($user->getRole() === 'ROLE_MANAGER') {
            throw $this->createAccessDeniedException("Accès refusé aux managers sur cet espace.");
        }

        /*
        if ($contract->getCreator() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas l'autorisation de télécharger ce contrat.");
        }
        */

        $pdfPath = $contract->getPdfPath();
        if (!$pdfPath || !file_exists($pdfPath)) {
            throw $this->createNotFoundException("Le fichier PDF du contrat est introuvable.");
        }

        $response = new BinaryFileResponse($pdfPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $contract->getContractNumber() . '.pdf'
        );

        return $response;
    }

    #[Route('/{id}/sign', name: 'app_contract_sign', methods: ['POST'])]
    public function sign(Contract $contract, EntityManagerInterface $em, Request $request): Response
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
        if ($contract->getCreator() !== $user) {
            throw $this->createAccessDeniedException("Seul le créateur peut signer ce contrat.");
        }
        */

        if ($contract->getStatus() !== 'SIGNED_BY_COLLABORATOR') {
            throw $this->createAccessDeniedException("Le contrat doit d'abord être signé par le collaborateur.");
        }

        $contract->setSignedByCreator(true);
        $contract->setCreatorSignatureDate(new \DateTime());
        $contract->setStatus('ACTIVE'); // Le contrat devient actif une fois signé par les deux parties

        $em->flush();

        $this->addFlash('success', 'Contract signed successfully. It is now active.');

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/{id}/complete', name: 'app_contract_complete', methods: ['POST'])]
    public function complete(Contract $contract, EntityManagerInterface $em, Request $request): Response
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
        if ($contract->getCreator() !== $user) {
            throw $this->createAccessDeniedException("Accès refusé.");
        }
        */

        if ($contract->getStatus() !== 'ACTIVE') {
            throw $this->createAccessDeniedException("Seuls les contrats actifs peuvent être clôturés.");
        }

        if ($contract->getEndDate() > new \DateTime()) {
            throw $this->createAccessDeniedException("Le contrat ne peut pas être clôturé avant sa date de fin.");
        }

        $contract->setStatus('COMPLETED');
        $em->flush();

        $this->addFlash('success', 'Contract marked as finished.');

        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
    }

    #[Route('/{id}/terminate', name: 'app_contract_terminate', methods: ['GET', 'POST'])]
    public function terminate(Contract $contract, Request $request, EntityManagerInterface $em): Response
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
        if ($contract->getCreator() !== $user) {
            throw $this->createAccessDeniedException("Accès refusé.");
        }
        */

        if ($contract->getStatus() !== 'ACTIVE') {
            throw $this->createAccessDeniedException("Seul un contrat actif peut être résilié.");
        }

        // On réutilise le RejectionReasonType pour saisir le motif de résiliation
        $form = $this->createForm(RejectionReasonType::class, null, [
            'attr' => ['novalidate' => 'novalidate']
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reason = $form->get('rejectionReason')->getData();

            $contract->setCancellationTerms($reason);
            $contract->setStatus('TERMINATED');

            $em->flush();

            $this->addFlash('danger', 'Contract has been terminated.');

            return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()]);
        }

        return $this->render('contract/terminate.html.twig', [
            'contract' => $contract,
            'form' => $form->createView(),
        ]);
    }
}
