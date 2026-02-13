<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $allowedRoles = ['ROLE_ADMIN', 'ROLE_Content_Creator'];
        $userRole = $request->getSession()->get('user_role');
        if (!in_array($userRole, $allowedRoles)) {
            $this->addFlash('warning', 'Access restricted to Visitors.');
            return $this->redirectToRoute('app_auth');
        }
        
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
