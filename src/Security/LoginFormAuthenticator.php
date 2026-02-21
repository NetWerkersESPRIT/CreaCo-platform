<?php

namespace App\Security;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_auth';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'login_check' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $captchaToken = $request->request->get('g-recaptcha-response');

        if (!$captchaToken) {
            throw new AuthenticationException('Please verify that you are not a robot.');
        }

        // Verify Recaptcha
        $client = \Symfony\Component\HttpClient\HttpClient::create();
        $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => '6LePq2osAAAAAJt8u-OPjMDsH95R5-zAXWtnktyB',
                'response' => $captchaToken,
                'remoteip' => $request->getClientIp(),
            ],
        ]);

        $data = $response->toArray();
        if (!($data['success'] ?? false)) {
            throw new AuthenticationException('Captcha verification failed.');
        }

        $user = $this->entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);

        if (!$user) {
            throw new AuthenticationException('User not found.');
        }

        if ($user->getPassword() === 'GOOGLE_AUTH') {
            throw new AuthenticationException('This is a Google user. Please login with Google.');
        }

        // Manual password check (plain text as currently used in AuthController)
        if ($password !== $user->getPassword()) {
            throw new AuthenticationException('Invalid password.');
        }

        $request->getSession()->set('_security.last_username', $email);

        return new SelfValidatingPassport(
            new UserBadge($email, function() use ($user) {
                return $user;
            }),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->getFlashBag()->add('error', $exception->getMessage());

        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        /** @var Users $user */
        $user = $token->getUser();
        
        // Keep the user's manual session variables for compatibility
        $request->getSession()->set('user_id', $user->getId());
        $request->getSession()->set('user_role', $user->getRole());
        $request->getSession()->set('groupid', $user->getGroupid());
        $request->getSession()->set('username', $user->getUsername());

        if ($user->getRole() === 'ROLE_ADMIN') {
            return new RedirectResponse($this->urlGenerator->generate('app_admin'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
