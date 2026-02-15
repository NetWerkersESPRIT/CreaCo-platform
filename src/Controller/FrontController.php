<?php

namespace App\Controller;

use App\Repository\CategorieCoursRepository;
use App\Repository\CoursRepository;
use App\Repository\RessourceRepository;
use App\Entity\CategorieCours;
use App\Entity\Cours;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
final class FrontController extends AbstractController
{   
    // READ LISTE DES CATEGORIES DE COUR
    #[Route('/front', name: 'app_front_home')]
    public function index(\Symfony\Component\HttpFoundation\Request $request, CategorieCoursRepository $catRepo): Response
    {
        $search = $request->query->get('search');
        if ($search) {
             $categories = $catRepo->searchByName($search);
        } else {
             $categories = $catRepo->findAll();
        }

        return $this->render('front/home/index.html.twig', [
            'categories' => $categories,
            'search' => $search
        ]);
    }

    // READ LISTE DES COURS PAR CATEGORIE (et recherche/filtrage dans cette catégorie)
    #[Route('/category/{id}', name: 'app_front_category')]
    public function category(\Symfony\Component\HttpFoundation\Request $request, CategorieCours $category, CoursRepository $coursRepo): Response
    {
        $search = $request->query->get('search');
        $sortParam = $request->query->get('sort', 'titre_ASC');
        
        // Handle combined format (e.g., "titre_ASC") or separate params
        if (str_contains($sortParam, '_')) {
            [$sortField, $sortOrder] = explode('_', $sortParam, 2);
        } else {
            $sortField = $sortParam;
            $sortOrder = $request->query->get('order', ($sortField === 'titre' ? 'ASC' : 'DESC'));
        }
        
        $filters = [
            'categorie' => $category->getId(),
            'search' => $search
        ];

        $sort = [
            'field' => $sortField,
            'order' => $sortOrder
        ];
        
        $courses = $coursRepo->findWithFilters($filters, $sort);

        return $this->render('front/category/show.html.twig', [
            'category' => $category,
            'courses' => $courses,
            'search' => $search,
            'current_sort' => $sortField,
            'current_order' => $sortOrder
        ]);
    }

    // READ LISTE DES RESSOURCES PAR COURS (avec filtrage par type et recherche)
    #[Route('/course/{id}', name: 'app_front_course')]
    public function course(\Symfony\Component\HttpFoundation\Request $request, Cours $course, RessourceRepository $resRepo): Response
    {
        $search = $request->query->get('search');
        $type = $request->query->get('type');

        $filters = [
            'cours' => $course->getId(),
            'search' => $search,
            'type' => $type
        ];

        $ressources = $resRepo->findWithFilters($filters);

        return $this->render('front/course/show.html.twig', [
            'course' => $course,
            'ressources' => $ressources,
            'search' => $search,
            'current_type' => $type
        ]);
    }
}
