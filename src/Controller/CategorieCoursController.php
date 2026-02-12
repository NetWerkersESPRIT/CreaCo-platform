<?php

namespace App\Controller;

use App\Entity\CategorieCours;
use App\Form\CategorieCoursType;
use App\Repository\CategorieCoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Route de base pour toutes les actions de ce contrôleur
#[Route('/categorie-cours')]
class CategorieCoursController extends AbstractController
{
    // READ LIST CATEG
    #[Route('/', name: 'app_categorie_cours_index', methods: ['GET'])]
    public function index(CategorieCoursRepository $categorieCoursRepository): Response
    {
        return $this->render('back/categorie_cours/index.html.twig', [
            'categorie_cours' => $categorieCoursRepository->findAll(),
        ]);
    }

    // CREATE CATEGORY
    #[Route('/new', name: 'app_categorie_cours_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CategorieCoursRepository $categorieCoursRepository): Response
    {   
        // Création d'une nouvelle instance de l'entité
        $categorieCours = new CategorieCours();
        // Création du formulaire lié à l'entité
        $form = $this->createForm(CategorieCoursType::class, $categorieCours);
        // traitement de la requete https
        $form->handleRequest($request);
        // verif formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            // sauvegarde de la categorie dans la BD
            $categorieCoursRepository->save($categorieCours, true);
            // redirection vers liste de categories
            return $this->redirectToRoute('app_categorie_cours_index', [], Response::HTTP_SEE_OTHER);
        }
        // affichhage du  form de creation de la categorie
        return $this->render('back/categorie_cours/new.html.twig', [
            'categorie_cours' => $categorieCours,
            'form' => $form,
        ]);
    }
    

    // READ CATEGORY BY ID
    #[Route('/{id}', name: 'app_categorie_cours_show', methods: ['GET'])]
    public function show(CategorieCours $categorieCours): Response
    {
        return $this->render('back/categorie_cours/show.html.twig', [
            'categorie_cours' => $categorieCours,
        ]);
    }

    // READ LISTE DES COURS D UNE CATEGORIE SPECIFIQUE
    #[Route('/{id}/courses', name: 'app_categorie_cours_courses', methods: ['GET'])]
    public function courses(CategorieCours $categorieCours): Response
    {
        return $this->render('back/categorie_cours/courses.html.twig', [
            'categorie_cours' => $categorieCours,
            'cours' => $categorieCours->getCours(),
        ]);
    }

    // UPDATE CATEGORY BY ID
    #[Route('/{id}/edit', name: 'app_categorie_cours_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategorieCours $categorieCours, CategorieCoursRepository $categorieCoursRepository): Response
    {
        // creation du formulaire avec données existantes
        $form = $this->createForm(CategorieCoursType::class, $categorieCours);
        // traitement requete https
        $form->handleRequest($request);
        // verif formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            // màj de la categorie
            $categorieCoursRepository->save($categorieCours, true);
            // redirection vers liste des categories
            return $this->redirectToRoute('app_categorie_cours_index', [], Response::HTTP_SEE_OTHER);
        }
        // affichage du formulaire de modif
        return $this->render('back/categorie_cours/edit.html.twig', [
            'categorie_cours' => $categorieCours,
            'form' => $form,
        ]);
    }

    // DELETE CAREGORY BY ID 
    #[Route('/{id}', name: 'app_categorie_cours_delete', methods: ['POST'])]
    public function delete(Request $request, CategorieCours $categorieCours, CategorieCoursRepository $categorieCoursRepository): Response
    {
        // verif de token avant suppression
        if ($this->isCsrfTokenValid('delete'.$categorieCours->getId(), $request->request->get('_token'))) {
            // suppression de la categorie de la BD
            $categorieCoursRepository->remove($categorieCours, true);
        }
        // redirection vers liste des categories
        return $this->redirectToRoute('app_categorie_cours_index', [], Response::HTTP_SEE_OTHER);
    }
}
