<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'app_notifications')]
    public function index(Request $request, NotificationRepository $repo, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_auth');
        }

        $user = $em->getRepository(Users::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_auth');
        }

        $notifications = $repo->findBy(['user_id' => $user], ['createdAt' => 'DESC']);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notification/{id}/read', name: 'app_notification_read', methods: ['GET'])]
    public function markAsRead(Notification $notification, EntityManagerInterface $em): Response
    {
        $notification->setIsRead(true);
        $em->flush();

        $targetUrl = $notification->getTargetUrl();
        return $this->redirect($targetUrl ?: $this->generateUrl('app_notifications'));
    }

    #[Route('/notifications/mark-all-seen', name: 'app_notifications_mark_all_seen', methods: ['POST'])]
    public function markAllAsSeen(Request $request, NotificationRepository $repo, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('app_auth');

        $user = $em->getRepository(Users::class)->find($userId);
        if (!$user) return $this->redirectToRoute('app_auth');

        $unread = $repo->findBy(['user_id' => $user, 'isRead' => false]);
        foreach ($unread as $notification) {
            $notification->setIsRead(true);
        }
        $em->flush();

        $this->addFlash('success', 'All notifications marked as seen.');
        return $this->redirectToRoute('app_notifications');
    }

    public function countUnread(Request $request, NotificationRepository $repo, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return new Response('0');
        }

        $user = $em->getRepository(Users::class)->find($userId);
        if (!$user) {
            return new Response('0');
        }

        return new Response((string)$repo->countUnreadForUser($user));
    }
}
