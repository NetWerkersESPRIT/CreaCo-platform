<?php

namespace App\EventListener;

use App\Repository\UsersRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class UserSessionListener implements EventSubscriberInterface
{
    private $usersRepository;
    private $twig;

    public function __construct(UsersRepository $usersRepository, Environment $twig)
    {
        $this->usersRepository = $usersRepository;
        $this->twig = $twig;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->getSession();
        $userId = $session->get('user_id');

        $user = null;
        if ($userId) {
            $user = $this->usersRepository->find($userId);
        }

        // Make user available in all Twig templates as 'app_user'
        $this->twig->addGlobal('app_user', $user);

        // Optional: Protect all pages except login/forgetpassword
        $route = $request->attributes->get('_route');
        $publicRoutes = ['app_auth', 'login_check', 'app_forgetpassword', 'app_useradd'];

        if (!$user && $route && !in_array($route, $publicRoutes)) {
            // Uncomment the next line if you want to force redirect to login
            // $event->setController(fn() => new RedirectResponse('/auth'));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
