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

// #[Route('/manager/contract')]
#[Route('/manager/contract')]
#[IsGranted('ROLE_MANAGER')]
class ManagerContractController extends AbstractController
{
    #[Route('/', name: 'app_manager_contract_index', methods: ['GET'])]
    public function index(ContractRepository $repo, EntityManagerInterface $em, #[CurrentUser] ?Users $user): Response
    {
        $user = $user ?? $em->getRepository(Users::class)->findOneBy([]);
        /*
        if ($user->getRole() !== 'manager') {
            throw $this->createAccessDeniedException("Accès réservé aux managers.");
        }
        */

        // Utilise la méthode personnalisée du repository pour trouver les contrats 
        // dont les CollabRequests associées ont ce manager comme réviseur.
        return $this->render('manager/contract/index.html.twig', [
            'contracts' => $repo->findForRevisor($user?->getId() ?? 0),
        ]);
    }

    #[Route('/{id}', name: 'app_manager_contract_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Contract $contract, EntityManagerInterface $em, #[CurrentUser] ?Users $user): Response
    {
        $user = $user ?? $em->getRepository(Users::class)->findOneBy([]);

        $collabRequest = $contract->getCollabRequest();
        if (!$collabRequest || $collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce contrat.");
        }

        return $this->render('manager/contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_manager_contract_edit', methods: ['GET', 'POST'])]
    public function edit(Contract $contract, Request $request, EntityManagerInterface $em, #[CurrentUser] ?Users $user): Response
    {
        $user = $user ?? $em->getRepository(Users::class)->findOneBy([]);

        $collabRequest = $contract->getCollabRequest();
        if (!$collabRequest || $collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce contrat.");
        }

        $form = $this->createForm(ContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Contrat mis à jour.');
            return $this->redirectToRoute('app_manager_contract_index');
        }

        return $this->render('manager/contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/send', name: 'app_manager_contract_send', methods: ['POST'])]
    public function send(Contract $contract, EntityManagerInterface $em, #[CurrentUser] ?Users $user): Response
    {
        $user = $user ?? $em->getRepository(Users::class)->findOneBy([]);

        $collabRequest = $contract->getCollabRequest();
        if (!$collabRequest || $collabRequest->getRevisor() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à ce contrat.");
        }

        if ($contract->getStatus() !== 'DRAFT') {
            $this->addFlash('warning', 'Ce contrat a déjà été envoyé.');
            return $this->redirectToRoute('app_manager_contract_index');
        }

        $contract->setStatus('SENT');
        $contract->setSentAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Le contrat a été envoyé pour signature.');
        return $this->redirectToRoute('app_manager_contract_index');
    }
}
