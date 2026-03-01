<?php

namespace App\Security;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Restores Symfony Security authentication from session when the user logged in
 * via the form (AuthController) instead of an authenticator. This makes the
 * profiler show "Authenticated: Yes" and the user identifier (email).
 */
class SessionAuthListener implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private EntityManagerInterface $em
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            return;
        }

        // Already authenticated (e.g. via Google)
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof Users) {
            return;
        }

        $user = $this->em->getRepository(Users::class)->find($userId);
        if (!$user) {
            $session->remove('user_id');
            $session->remove('user_role');
            $session->remove('username');
            return;
        }

        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );
        $this->tokenStorage->setToken($token);
    }
}
