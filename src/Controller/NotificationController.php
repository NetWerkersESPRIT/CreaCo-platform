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

use Symfony\Component\HttpFoundation\JsonResponse;

class NotificationController extends AbstractController
{
    #[Route('/api/notifications/latest', name: 'app_api_notifications_latest', methods: ['GET'])]
    public function getLatestJSON(Request $request, NotificationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return new JsonResponse([], 401);

        $user = $em->getRepository(Users::class)->find($userId);
        if (!$user) return new JsonResponse([], 401);

        $notifications = $repo->findBy(['user_id' => $user], ['createdAt' => 'DESC'], 5);

        $data = [];
        foreach ($notifications as $n) {
            $data[] = [
                'id' => $n->getId(),
                'message' => $n->getMessage(),
                'isRead' => $n->isRead(),
                'createdAt' => $n->getCreatedAt()->format('c'),
                'type' => $n->getType(),
                'status' => $n->getStatus()
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/notifications/unread-count', name: 'app_api_notifications_unread_count', methods: ['GET'])]
    public function getUnreadCountJSON(Request $request, NotificationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return new JsonResponse(['count' => 0]);

        $user = $em->getRepository(Users::class)->find($userId);
        if (!$user) return new JsonResponse(['count' => 0]);

        return new JsonResponse(['count' => $repo->countUnreadForUser($user)]);
    }

    private function formatTimeAgo(\DateTimeInterface $datetime): string
    {
        $now = new \DateTime();
        $interval = $now->diff($datetime);
        
        if ($interval->y > 0) return $interval->y . 'y ago';
        if ($interval->m > 0) return $interval->m . 'm ago';
        if ($interval->d > 0) return $interval->d . 'd ago';
        if ($interval->h > 0) return $interval->h . 'h ago';
        if ($interval->i > 0) return $interval->i . 'm ago';
        return 'just now';
    }
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
        $targetUrl = $notification->getTargetUrl();
        $em->remove($notification);
        $em->flush();

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
            $em->remove($notification);
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
