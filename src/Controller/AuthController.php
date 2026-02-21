<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Users;
use Symfony\Component\HttpClient\HttpClient;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;

final class AuthController extends AbstractController
{
    #[Route('/', name: 'app_auth')]
    public function index(): Response
    {
        return $this->render('auth/index.html.twig', [
            'controller_name' => 'AuthController',
            'error' => null,
        ]);
    }

    #[Route('/login-check', name: 'login_check', methods: ['POST'])]
    public function loginCheck(): void
    {
        // This method will be intercepted by the LoginFormAuthenticator
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/login/google', name: 'login_google')]
    public function loginGoogle(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google_login')
            ->redirect(
                ['email', 'profile'],
                ['state' => 'login']
            );
    }

    #[Route('/login/google/check', name: 'login_google_check')]
    public function loginGoogleCheck(): void
    {
    }

}
