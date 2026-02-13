<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(\Symfony\Component\HttpFoundation\Request $request): Response
    {
        $request->getSession()->clear();
        $this->addFlash('success', 'You have been logged out.');
        return $this->redirectToRoute('app_auth');
    }
}
