<?php

namespace App\Security;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleLoginAuthenticator extends OAuth2Authenticator
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

    // ✅ Only intercept the Google login callback
    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'login_google_check';
    }

    // ✅ Authenticate the Google user
    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google_login');
        $accessToken = $this->fetchAccessToken($client);
        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);

        $email = $googleUser->getEmail();

        // Find existing user
        $user = $this->em->getRepository(Users::class)->findOneBy(['email' => $email]);

        if (!$user) {
            throw new AuthenticationException('This is not a Google user.');
        }

        // Only Google users can login via this flow
        if ($user->getPassword() !== 'GOOGLE_AUTH') {
            throw new AuthenticationException('This account must login with email/password.');
        }

        // Create Passport
        return new SelfValidatingPassport(new UserBadge($email, function ($userIdentifier) use ($user) {
            return $user;
        }));
    }

    // ✅ On successful login
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $user = $token->getUser();

        $request->getSession()->set('user_id', $user->getId());
        $request->getSession()->set('user_role', $user->getRole());
        $request->getSession()->set('username', $user->getUsername());

        $this->addFlash($request, 'success', 'Welcome back, '.$user->getUsername().'!');

        // Redirect based on role
        switch ($user->getRole()) {
            case 'ROLE_ADMIN':
                return new RedirectResponse($this->router->generate('app_admin'));
            default:
                return new RedirectResponse($this->router->generate('app_home'));
        }
    }

    // ✅ On failure
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        $request->getSession()?->getFlashBag()->add('error', $exception->getMessage());
        return new RedirectResponse($this->router->generate('app_auth'));
    }

    private function addFlash(Request $request, string $type, string $message): void
    {
        $request->getSession()?->getFlashBag()->add($type, $message);
    }
}
