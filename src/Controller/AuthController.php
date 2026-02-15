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
    #[Route('/auth', name: 'app_auth')]
    public function index(): Response
    {
        return $this->render('auth/index.html.twig', [
            'controller_name' => 'AuthController',
            'error' => null,
        ]);
    }

    #[Route('/login-check', name: 'login_check', methods: ['POST'])]
    public function loginCheck(Request $request, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('g-recaptcha-response');

        if (!$token) {
            $this->addFlash('error', 'Please verify that you are not a robot.');
            return $this->redirectToRoute('app_auth');
        }

        $client = HttpClient::create();
        $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret'   => '6LePq2osAAAAAJt8u-OPjMDsH95R5-zAXWtnktyB',
                'response' => $token,
                'remoteip' => $request->getClientIp(),
            ],
        ]);

        $data = $response->toArray();

        if (!($data['success'] ?? false)) {
            $this->addFlash('error', 'Captcha verification failed.');
            return $this->redirectToRoute('app_auth');
        }

        $email = $request->request->get('email');
        $password = $request->request->get('password');

        $user = $em->getRepository(Users::class)->findOneBy(['email' => $email]);

        // ❌ user not found
        if (!$user) {
            $error = 'User not found';
            return $this->render('auth/index.html.twig', [
                'error' => $error,
                'email' => $email
            ]);
        }

        if ($user->getPassword() === 'GOOGLE_AUTH') {
        return $this->render('auth/index.html.twig', [
            'error' => 'This is a Google user. Please login with Google.',
            'email' => $email
        ]);
    }

        // ❌ wrong password
        if (($password != $user->getPassword())) {
            $error = 'Invalid password';
            return $this->render('auth/index.html.twig', [
                'error' => $error,
                'email' => $email
            ]);
        }

        // ✅ redirect by role
        if ($user) {
            $request->getSession()->set('user_id', $user->getId());
            $request->getSession()->set('user_role', $user->getRole());
            $request->getSession()->set('username', $user->getUsername());

            $this->addFlash('success', 'Welcome back, ' . $user->getUsername() . '!');

            switch ($user->getRole()) {
             
                case 'ROLE_ADMIN':
                    return $this->redirectToRoute('app_admin');

                default:
                    return $this->redirectToRoute('app_home');
            }
        }

        $error = 'Unknown error';
        return $this->render('auth/index.html.twig', [
            'error' => $error,
            'email' => $email
        ]);
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
