<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use App\Repository\UsersRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private UsersRepository $usersRepository,
        private RequestStack $requestStack
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_unread_notif_count', [$this, 'getUnreadNotifCount']),
        ];
    }

    public function getUnreadNotifCount(): int
    {
        $session = $this->requestStack->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            return 0;
        }

        $user = $this->usersRepository->find($userId);
        if (!$user) {
            return 0;
        }

        return $this->notificationRepository->countUnreadForUser($user);
    }
}
