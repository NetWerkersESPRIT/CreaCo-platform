<?php

namespace App\Controller;

use App\Repository\CategorieCoursRepository;
use App\Repository\CoursRepository;
use App\Repository\RessourceRepository;
use App\Entity\CategorieCours;
use App\Entity\Cours;
use App\Service\GamificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
final class FrontController extends AbstractController
{   
    // READ LISTE DES CATEGORIES DE COUR
    #[Route('/front', name: 'app_front_home')]
    public function index(\Symfony\Component\HttpFoundation\Request $request, CategorieCoursRepository $catRepo): Response
    {
        // Redirection Admin vers Back-office
        $userRole = $request->getSession()->get('user_role');
        if ($userRole === 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_categorie_cours_index');
        }

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
    public function category(
        \Symfony\Component\HttpFoundation\Request $request,
        CategorieCours $category,
        CoursRepository $coursRepo,
        GamificationService $gamificationService,
        EntityManagerInterface $em
    ): Response {
        // Redirection Admin vers Back-office (Liste des cours de la catégorie)
        $userRole = $request->getSession()->get('user_role');
        if ($userRole === 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_categorie_cours_courses', ['id' => $category->getId()]);
        }

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

        $categoryCompleted = false;
        $userId = $request->getSession()->get('user_id');
        if ($userId) {
            $user = $em->getRepository(\App\Entity\Users::class)->find($userId);
            if ($user) {
                $categoryCompleted = $gamificationService->hasUserCompletedCategory($user, $category);
            }
        }

        return $this->render('front/category/show.html.twig', [
            'category' => $category,
            'courses' => $courses,
            'search' => $search,
            'current_sort' => $sortField,
            'current_order' => $sortOrder,
            'category_completed' => $categoryCompleted,
        ]);
    }

    /**
     * Certificate for completing all resources in all courses of a category.
     * Only accessible to logged-in users who have completed the category.
     */
    #[Route('/certificate/category/{id}', name: 'app_front_certificate_category', methods: ['GET'])]
    public function certificateCategory(
        \Symfony\Component\HttpFoundation\Request $request,
        CategorieCours $category,
        GamificationService $gamificationService,
        EntityManagerInterface $em
    ): Response {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            throw new AccessDeniedHttpException('You must be logged in to view this certificate.');
        }
        $user = $em->getRepository(\App\Entity\Users::class)->find($userId);
        if (!$user) {
            throw new AccessDeniedHttpException('User not found.');
        }
        if (!$gamificationService->hasUserCompletedCategory($user, $category)) {
            throw new AccessDeniedHttpException('You must complete all resources in all courses of this category to obtain the certificate.');
        }

        return $this->render('front/certificate/category.html.twig', [
            'category' => $category,
            'user' => $user,
            'issued_at' => new \DateTime(),
        ]);
    }

    // READ LISTE DES RESSOURCES PAR COURS (avec filtrage par type et recherche)
    #[Route('/course/{id}', name: 'app_front_course')]
    public function course(
        \Symfony\Component\HttpFoundation\Request $request,
        Cours $course,
        RessourceRepository $resRepo,
        \Doctrine\ORM\EntityManagerInterface $em,
        \App\Service\GamificationService $gamificationService = null
    ): Response
    {
        // Redirection Admin vers Back-office (Détail du cours)
        $userRole = $request->getSession()->get('user_role');
        if ($userRole === 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_cours_show', ['id' => $course->getId()]);
        }

        // INCREMENT VIEW COUNT
        $course->setViews(($course->getViews() ?? 0) + 1);
        $em->flush();

        $search = $request->query->get('search');
        $type = $request->query->get('type');

         $filters = [
             'cours' => $course->getId(),
             'search' => $search,
             'type' => $type
         ];

         $ressources = $resRepo->findWithFilters($filters);

         // Get user progress for this course
         $progressData = null;
         $userId = $request->getSession()->get('user_id');
         if ($userId && $gamificationService) {
             $user = $em->getRepository(\App\Entity\Users::class)->find($userId);
             if ($user) {
                 $progressData = $gamificationService->getUserCourseProgress($user, $course);
             }
         }

         return $this->render('front/course/show.html.twig', [
             'course' => $course,
             'ressources' => $ressources,
             'search' => $search,
             'current_type' => $type,
             'progress' => $progressData
         ]);
    }
}
