<?php

namespace App\Security;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleSignupAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $em;
    private RouterInterface $router;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $em, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
    }

    // 🔹 Symfony 6+ : on intercepte seulement la route signup
    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'google_signup_check';
    }

    // 🔹 Crée l'utilisateur si nécessaire
    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google_signup');
        $accessToken = $this->fetchAccessToken($client);
        $googleUser = $client->fetchUserFromToken($accessToken);

        $email = $googleUser->getEmail();
        $userRepository = $this->em->getRepository(Users::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user) {
            throw new AuthenticationException('This email is already registered.');
        }

        if (!$user) {
            $user = new Users();
            $user->setEmail($email);
            $user->setUsername($googleUser->getName() ?? 'google_user');
            $user->setRole('ROLE_CONTENT_CREATOR');
            $user->setPassword('GOOGLE_AUTH');
            $this->em->persist($user);
            $this->em->flush();
            $user->setGroupid($user->getId());
            $this->em->flush();
        }
        return new SelfValidatingPassport(new UserBadge($email, function ($userIdentifier) use ($user) {
            return $user;
        }));
    }
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        return new RedirectResponse($this->router->generate('app_auth'));
    }
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        $session = $request->getSession();

        if ($session) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }

        return new RedirectResponse($this->router->generate('app_useradd'));
    }
}
