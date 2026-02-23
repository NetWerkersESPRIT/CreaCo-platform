<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Form\RessourceType;
use App\Repository\RessourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ressource')]
final class RessourceController extends AbstractController
{
    #[Route('/upload-image', name: 'app_image_upload', methods: ['POST'])]
    public function uploadImage(Request $request): JsonResponse
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return new JsonResponse(['error' => ['message' => 'Access denied']], 403);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('upload') ?? $request->files->get('file');

        if (!$file) {
            return new JsonResponse(['error' => ['message' => 'No file uploaded']], 400);
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return new JsonResponse(['error' => ['message' => 'Invalid file type']], 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/ressource_images';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid() . '.' . $file->guessExtension();
        $file->move($uploadDir, $filename);

        $url = '/uploads/ressource_images/' . $filename;

        return new JsonResponse([
            'uploaded' => true,
            'url' => $url,
            'fileName' => $filename
        ]);
    }

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
	    public function new(Request $request, EntityManagerInterface $entityManager, \App\Repository\CoursRepository $coursRepo): Response
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
	            $file = $ressource->getFile();
	            $contenu = $ressource->getContenu();

	            // Validation selon la nature de la ressource
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
	                // Déterminer le type de ressource à partir du mime type
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

	                // On nettoie le contenu texte si on a un fichier
	                $ressource->setContenu(null);
		            } elseif ($nature === 'texte') {
		                // Ressource purement textuelle
		                $ressource->setType('TEXTE');
		                $ressource->setUrl(null);   // aucune URL de fichier
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
            // Mark resource as opened and award XP
            $result = $gamificationService->markResourceAsOpened($user, $ressource);

            if ($result['first_open']) {
                $this->addFlash('success', sprintf(
                    'Félicitations ! Vous avez gagné %d XP ! Total : %d XP',
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
	    public function edit(Request $request, Ressource $ressource, EntityManagerInterface $entityManager): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

	        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

	        if ($form->isSubmitted() && $form->isValid()) {
	            $nature = $form->get('nature')->getData();
	            $file = $ressource->getFile();
	            $contenu = $ressource->getContenu();
	
		            if ($nature === 'fichier') {
	                // Si aucune ressource existante et aucun nouveau fichier, on bloque
	                if (!$file && !$ressource->getUrl()) {
	                    $this->addFlash('error', 'Veuillez sélectionner un fichier.');
	                    return $this->render('back/ressource/edit.html.twig', [
	                        'ressource' => $ressource,
	                        'form' => $form->createView(),
	                    ]);
	                }

	                // Si un nouveau fichier a  e9t 0e9 envoy 0e9, on met  e0 jour le type
	                if ($file) {
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

	                    // On nettoie le contenu texte si on repasse sur un fichier
	                    $ressource->setContenu(null);
	                }
		            } elseif ($nature === 'texte') {
	                if (empty($contenu)) {
	                    $this->addFlash('error', 'Veuillez saisir du contenu texte.');
	                    return $this->render('back/ressource/edit.html.twig', [
	                        'ressource' => $ressource,
	                        'form' => $form->createView(),
	                    ]);
	                }
		                $ressource->setType('TEXTE');
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