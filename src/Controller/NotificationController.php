<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Users;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'app_notifications', methods: ['GET'])]
    public function index(Request $request, NotificationRepository $repo, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_auth');
        }

        $notifications = $repo->findBy(
            ['user_id' => $userId],
            ['createdAt' => 'DESC']
        );

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/api/unread-count', name: 'api_notifications_unread_count', methods: ['GET'])]
    public function unreadCount(Request $request, NotificationRepository $repo): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $count = $repo->count(['user_id' => $userId, 'isRead' => false]);

        return new JsonResponse(['count' => $count]);
    }

    #[Route('/api', name: 'api_notifications_list', methods: ['GET'])]
    public function list(Request $request, NotificationRepository $repo): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $notifications = $repo->findBy(
            ['user_id' => $userId, 'isRead' => false],
            ['createdAt' => 'DESC'],
            10 // Last 10 unread
        );

        $data = [];
        foreach ($notifications as $notification) {
            $data[] = [
                'id' => $notification->getId(),
                'message' => $notification->getMessage(),
                'isRead' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()->format('d M H:i'),
                'targetUrl' => $notification->getTargetUrl(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/{id}/read', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markRead(Notification $notification, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId || $notification->getUserId()->getId() !== $userId) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $notification->setIsRead(true);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
