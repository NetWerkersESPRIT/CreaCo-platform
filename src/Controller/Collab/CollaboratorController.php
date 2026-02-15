<?php

namespace App\Controller\Collab;

use App\Entity\Collaborator;
use App\Entity\Users;
use App\Form\CollaboratorType;
use App\Repository\CollaboratorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/collaborator')]
class CollaboratorController extends AbstractController
{
    #[Route('/', name: 'app_collaborator_index', methods: ['GET'])]
    public function index(Request $request, CollaboratorRepository $repository, EntityManagerInterface $em): Response
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

        $search = $request->query->get('q', '');

        if (!empty($search)) {
            $collaborators = $repository->findBySearchQuery($search, $user->getId());
        } else {
            $collaborators = $repository->findVisibleForUser($user->getId());
        }

        // Si c'est une requête AJAX, on renvoie uniquement la liste partielle
        if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->render('collaborator/_list.html.twig', [
                'collaborators' => $collaborators,
            ]);
        }

        return $this->render('collaborator/index.html.twig', [
            'collaborators' => $collaborators,
            'searchTerm' => $search,
        ]);
    }

    #[Route('/new', name: 'app_collaborator_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
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

        $collaborator = new Collaborator();
        $form = $this->createForm(CollaboratorType::class, $collaborator);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $collaborator->setAddedBy($user);
            $collaborator->setIsPublic(true); // Rendu public par défaut pour faciliter les tests sans auth

            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logoFile')->getData();

            if ($logoFile) {
                $newFilename = uniqid() . '.' . $logoFile->guessExtension();
                try {
                    $logoFile->move(
                        $this->getParameter('logos_directory'),
                        $newFilename
                    );
                    $collaborator->setLogo($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', "Error uploading logo.");
                }
            }

            $em->persist($collaborator);
            $em->flush();

            $this->addFlash('success', 'Collaborator created successfully.');

            return $this->redirectToRoute('app_collaborator_index');
        }

        return $this->render('collaborator/new.html.twig', [
            'collaborator' => $collaborator,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_collaborator_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Collaborator $collaborator, EntityManagerInterface $em, Request $request): Response
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
        if (!$collaborator->isVisibleForUser($user->getId())) {
            throw $this->createAccessDeniedException("Vous n'avez pas l'autorisation de voir ce collaborateur.");
        }
        */

        return $this->render('collaborator/show.html.twig', [
            'collaborator' => $collaborator,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_collaborator_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Collaborator $collaborator, Request $request, EntityManagerInterface $em): Response
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
        if ($collaborator->getAddedByUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException("Seul le créateur peut modifier ce collaborateur.");
        }
        */

        $form = $this->createForm(CollaboratorType::class, $collaborator);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logoFile')->getData();

            if ($logoFile) {
                $newFilename = uniqid() . '.' . $logoFile->guessExtension();
                try {
                    $logoFile->move(
                        $this->getParameter('logos_directory'),
                        $newFilename
                    );
                    $collaborator->setLogo($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', "Error uploading logo.");
                }
            }

            $em->flush();

            $this->addFlash('success', 'Collaborator updated successfully.');

            return $this->redirectToRoute('app_collaborator_index');
        }

        return $this->render('collaborator/edit.html.twig', [
            'collaborator' => $collaborator,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_collaborator_delete', methods: ['POST', 'DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Collaborator $collaborator, EntityManagerInterface $em, Request $request): Response
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
        if ($collaborator->getAddedByUserId() !== $user->getId()) {
            throw $this->createAccessDeniedException("Seul le créateur peut supprimer ce collaborateur.");
        }
        */

        $em->remove($collaborator);
        $em->flush();

        $this->addFlash('success', 'Collaborator deleted.');

        return $this->redirectToRoute('app_collaborator_index');
    }
}
