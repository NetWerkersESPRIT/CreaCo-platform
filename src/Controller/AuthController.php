<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Users;
use App\Repository\CoursRepository;
use Symfony\Component\HttpClient\HttpClient;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;

final class AuthController extends AbstractController
{
    #[Route('/', name: 'app_visitor')]
    public function index(CoursRepository $coursRepo): Response
    {
        // pull a handful of random courses for displaying on the visitor landing page
        $randomCourses = $coursRepo->findRandom(5);

        return $this->render('auth/visitor.html.twig', [
            'controller_name' => 'AuthController',
            'error' => null,
            'random_courses' => $randomCourses,
        ]);
    }


    #[Route('/auth', name: 'app_auth')]
    public function authenticate(): Response
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
