<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Form\CoursType;
use App\Repository\CoursRepository;
use App\Repository\CategorieCoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Route de base pour toutes les actions de ce contrôleur
#[Route('/cours')]
class CoursController extends AbstractController
{
    // READ LIST COURS
    // READ LIST COURS

    #[Route('/', name: 'app_cours_index', methods: ['GET'])]
    public function index(Request $request, CoursRepository $coursRepository): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        $filters = [
            'search' => $request->query->get('search'),
            'titre'  => $request->query->get('titre'),
            'categorie' => $request->query->get('categorie'),
        ];

        $sort = [
            'field' => $request->query->get('sort'),
            'order' => $request->query->get('order', 'DESC'),
        ];

        $cours = $coursRepository->findWithFilters($filters, $sort);

        return $this->render('back/cours/index.html.twig', [
            'cours' => $cours,
            'filters' => $filters,
            'sort' => $sort,
            'search' => $filters['search'],
        ]);
    }

    //CREATE COURS
    #[Route('/new', name: 'app_cours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CategorieCoursRepository $categoryRepo, \Symfony\Component\String\Slugger\SluggerInterface $slugger): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        // nouvelle instance de cours
        $cours = new Cours();
        $cours->setDateDeCreation(new \DateTime()); // Set creation date
        
        // récup del'id de la categorie de ce cours
        $catId = $request->query->get('category');
        // selection de la categorie si elle existe et null si elle n'existe pas
        $selectedCategory = $catId ? $categoryRepo->find($catId) : null;

        // si une categorie est selectionnée on l'assicie au cours
        if ($selectedCategory) {
            $cours->setCategorie($selectedCategory);
        }

        // creation du form lié à l'entité cours
        $form = $this->createForm(CoursType::class, $cours);
        // traitement requete https
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
             /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/cours',
                        $newFilename
                    );
                } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException) {
                    // Gestion des erreurs d'upload
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }

                // Store relative path or filename
                $cours->setImage('/uploads/cours/' . $newFilename);
            }

            // Réaffectation de la catégorie sélectionnée 
            if ($selectedCategory) {
                $cours->setCategorie($selectedCategory);
            }
            // Préparation de l'entité pour l'insertion en base de données
            $entityManager->persist($cours);
            // Exécution de la requête SQL (INSERT)
            $entityManager->flush();
            // redirection vers liste des cours
            return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
        }
        // affichage du formulaire de creation de cours
        return $this->render('back/cours/new.html.twig', [
            'cours' => $cours,
            'form' => $form,
            'selected_category' => $selectedCategory,
        ]);
    }
    // READ COURS BY ID
    #[Route('/{id}', name: 'app_cours_show', methods: ['GET'])]
    public function show(Request $request, Cours $cours): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        return $this->render('back/cours/show.html.twig', [
            'cours' => $cours,
        ]);
    }
    // READ LISTE DES RESSOURCES D UN COURS SPECIFIQUE 
    #[Route('/{id}/resources', name: 'app_cours_resources', methods: ['GET'])]
    public function resources(Request $request, Cours $cours): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        return $this->render('back/cours/resources.html.twig', [
            'cours' => $cours,
            'ressources' => $cours->getRessources(),
        ]);
    }
    // UPDATE COURS BY ID
    #[Route('/{id}/edit', name: 'app_cours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cours $cours, CoursRepository $coursRepository, \Symfony\Component\String\Slugger\SluggerInterface $slugger): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        // creation du formulaire avec données existantes
        $form = $this->createForm(CoursType::class, $cours);
        // traitement requete https
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
             /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/cours',
                        $newFilename
                    );
                } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException) {
                    // Gestion des erreurs d'upload
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');

                }

                $cours->setImage('/uploads/cours/' . $newFilename);
            }
            // sauvegarde de la categorie
            $coursRepository->save($cours, true);

            // Message de succès
            $this->addFlash('success', 'Le cours a été modifié avec succès !');

            // redirection vers liste des cours
            return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
        }

        // Si le formulaire a été soumis mais n'est pas valide, afficher un message d'erreur
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez vérifier les champs en rouge.');
        }

        // affichage du formulaire de modification de cours
        return $this->render('back/cours/edit.html.twig', [
            'cours' => $cours,
            'form' => $form->createView(),
            'selected_category' => null,
        ]);
    }
    // DELETE COURS BY ID
    #[Route('/{id}', name: 'app_cours_delete', methods: ['POST'])]
    public function delete(Request $request, Cours $cours, CoursRepository $coursRepository): Response
    {
        // Check if user is admin
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Access denied. Admin role required.');
        }

        // verif de token avant suppression
        if ($this->isCsrfTokenValid('delete'.$cours->getId(), $request->request->get('_token'))) {
            // suppression de la categorie
            $coursRepository->remove($cours, true);
        }
        // redirection vers liste des cours
        return $this->redirectToRoute('app_cours_index', [], Response::HTTP_SEE_OTHER);
    }
}
