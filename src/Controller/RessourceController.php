<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Form\RessourceType;
use App\Repository\RessourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/ressource')]
final class RessourceController extends AbstractController
{
    #[Route(name: 'app_ressource_index', methods: ['GET'])]
    public function index(Request $request, RessourceRepository $ressourceRepository): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        $filters = [
            'search' => $request->query->get('search'),
            'type'   => $request->query->get('type'),
            'cours'  => $request->query->get('cours'), // ID or Title
            'nom'    => $request->query->get('nom'),
        ];

        $sort = [
            'field' => $request->query->get('sort'),
            'order' => $request->query->get('order', 'DESC'),
        ];

        $ressources = $ressourceRepository->findWithFilters($filters, $sort);

        return $this->render('back/ressource/index.html.twig', [
            'ressources' => $ressources,
            'filters' => $filters,
            'sort' => $sort,
            'search' => $filters['search'], // Compatibility
        ]);
    }

    #[Route('/new', name: 'app_ressource_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, \App\Repository\CoursRepository $coursRepo, SluggerInterface $slugger): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        $ressource = new Ressource();
        $courseId = $request->query->get('course');
        $selectedCourse = $courseId ? $coursRepo->find($courseId) : null;

        if ($selectedCourse) {
            $ressource->setCours($selectedCourse);
        }

        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nature = $form->get('nature')->getData();
            $file = $form->get('fichier')->getData();
            $contenu = $form->get('contenu')->getData();

            // Validate based on nature
            if ($nature === 'fichier' && !$file) {
                 $this->addFlash('error', 'Veuillez sélectionner un fichier.');
                 return $this->render('back/ressource/new.html.twig', [
                    'ressource' => $ressource,
                    'form' => $form->createView(),
                    'selected_course' => $selectedCourse,
                ]);
            }
            if ($nature === 'texte' && empty($contenu)) {
                $this->addFlash('error', 'Veuillez saisir du contenu texte.');
                 return $this->render('back/ressource/new.html.twig', [
                    'ressource' => $ressource,
                    'form' => $form->createView(),
                    'selected_course' => $selectedCourse,
                ]);
            }

            if ($nature === 'fichier' && $file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/ressources';
                    
                    if (!is_dir($uploadDirectory)) {
                        mkdir($uploadDirectory, 0777, true);
                    }

                    // On récupère le mimeType AVANT de déplacer le fichier
                    $mimeType = $file->getMimeType();

                    $file->move($uploadDirectory, $newFilename);
                    $ressource->setUrl('/uploads/ressources/'.$newFilename);
                    
                    if (str_contains($mimeType, 'pdf')) {
                        $ressource->setType('PDF');
                    } elseif (str_contains($mimeType, 'image')) {
                        $ressource->setType('IMAGE');
                    } elseif (str_contains($mimeType, 'video')) {
                        $ressource->setType('VIDEO');
                    } else {
                        $ressource->setType('FILE');
                    }
                    
                    // Clear content if file uploaded (optional, but cleaner)
                    $ressource->setContenu(null);
                    
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload : ' . $e->getMessage());
                    return $this->render('back/ressource/new.html.twig', [
                        'ressource' => $ressource,
                        'form' => $form->createView(),
                        'selected_course' => $selectedCourse,
                    ]);
                }
            } elseif ($nature === 'texte') {
                $ressource->setType('FILE'); // Default type for text resources
                $ressource->setUrl(null);   // Ensure no URL
            }

            // Important: Force re-assignment of course if it was pre-selected
            if ($selectedCourse) {
                $ressource->setCours($selectedCourse);
            }

            try {
                $entityManager->persist($ressource);
                $entityManager->flush();
                $this->addFlash('success', 'Ressource créée avec succès !');
                return $this->redirectToRoute('app_ressource_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
            }
        }

        return $this->render('back/ressource/new.html.twig', [
            'ressource' => $ressource,
            'form' => $form->createView(),
            'selected_course' => $selectedCourse,
        ]);
    }

    #[Route('/{id}', name: 'app_ressource_show', methods: ['GET'])]
    public function show(Request $request, Ressource $ressource): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        return $this->render('back/ressource/show.html.twig', [
            'ressource' => $ressource,
        ]);
    }

    #[Route('/view/{id}', name: 'app_ressource_view', methods: ['GET'])]
    public function view(
        Request $request,
        Ressource $ressource,
        EntityManagerInterface $entityManager,
        \App\Service\GamificationService $gamificationService
    ): Response
    {
        // Check if user is authenticated (ROLE_CONTENT_CREATOR, ROLE_MANAGER, ROLE_MEMBER, ROLE_ADMIN)
        $allowedRoles = ['ROLE_CONTENT_CREATOR', 'ROLE_MANAGER', 'ROLE_MEMBER', 'ROLE_ADMIN'];
        $userRole = $request->getSession()->get('user_role');

        if (!in_array($userRole, $allowedRoles)) {
            throw $this->createAccessDeniedException('Access denied. You must be logged in to view resources.');
        }

        // Get current user
        $userId = $request->getSession()->get('user_id');
        $user = $userId ? $entityManager->getRepository(\App\Entity\Users::class)->find($userId) : null;

        if ($user) {
            // Mark resource as opened and award points
            $result = $gamificationService->markResourceAsOpened($user, $ressource);

            if ($result['first_open']) {
                $this->addFlash('success', sprintf(
                    'Félicitations ! Vous avez gagné %d points ! Total: %d points',
                    $result['points_earned'],
                    $result['total_points']
                ));
            }
        }

        return $this->render('front/ressource/view.html.twig', [
            'ressource' => $ressource,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ressource_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ressource $ressource, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nature = $form->get('nature')->getData();
            $file = $form->get('fichier')->getData();
            $contenu = $form->get('contenu')->getData();

            if ($nature === 'fichier' && $file) {
                // ... file upload logic ...
                 $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/ressources';
                    
                    if (!is_dir($uploadDirectory)) {
                        mkdir($uploadDirectory, 0777, true);
                    }

                    $file->move($uploadDirectory, $newFilename);
                    $ressource->setUrl('/uploads/ressources/'.$newFilename);
                    
                    $mimeType = $file->getMimeType();
                    if (str_contains($mimeType, 'pdf')) {
                        $ressource->setType('PDF');
                    } elseif (str_contains($mimeType, 'image')) {
                        $ressource->setType('IMAGE');
                    } elseif (str_contains($mimeType, 'video')) {
                        $ressource->setType('VIDEO');
                    } else {
                        $ressource->setType('FILE');
                    }
                    
                    // Clear content if switching to file and uploading new one
                    $ressource->setContenu(null);
                    
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload : ' . $e->getMessage());
                    return $this->render('back/ressource/edit.html.twig', [
                        'ressource' => $ressource,
                        'form' => $form->createView(),
                    ]);
                }
            } elseif ($nature === 'texte') {
                if (empty($contenu)) {
                    $this->addFlash('error', 'Veuillez saisir du contenu texte.');
                    return $this->render('back/ressource/edit.html.twig', [
                        'ressource' => $ressource,
                        'form' => $form->createView(),
                    ]);
                }
                $ressource->setType('FILE');
                $ressource->setUrl(null);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Ressource modifiée avec succès !');

            return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/ressource/edit.html.twig', [
            'ressource' => $ressource,
            'form' => $form->createView(),
            'selected_course' => null,
        ]);
    }

    #[Route('/{id}', name: 'app_ressource_delete', methods: ['POST'])]
    public function delete(Request $request, Ressource $ressource, EntityManagerInterface $entityManager): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        if ($this->isCsrfTokenValid('delete'.$ressource->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($ressource);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_ressource_index', [], Response::HTTP_SEE_OTHER);
    }
}