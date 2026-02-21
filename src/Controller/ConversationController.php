<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\Users;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/messages')]
class ConversationController extends AbstractController
{
    #[Route('/conversation/{id}', name: 'app_conversation_show', methods: ['GET', 'POST'])]
    public function show(Conversation $conversation, Request $request, EntityManagerInterface $em): Response
    {
        $sessionUserId = $request->getSession()->get('user_id');
        if (!$sessionUserId) {
            return $this->redirectToRoute('app_auth');
        }

        /** @var Users|null $currentUser */
        $currentUser = $em->getRepository(Users::class)->find($sessionUserId);
        $isAdmin = $currentUser && $currentUser->getRole() === 'ROLE_ADMIN';

        // Authorization check
        $isOwner = $conversation->getOwnerUser()->getId() === $sessionUserId;
        $isAdmin = $currentUser && $currentUser->getRole() === 'ROLE_ADMIN';

        if (!$isOwner && !$isAdmin) {
            throw $this->createAccessDeniedException('You are not authorized to view this conversation.');
        }

        // Handle new message (Synchronous fallback)
        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            if ($content) {
                $message = new Message();
                $message->setConversation($conversation);
                $message->setSenderUser($currentUser);
                $message->setContent($content);
                $em->persist($message);
                $em->flush();

                return $this->redirectToRoute('app_conversation_show', ['id' => $conversation->getId()]);
            }
        }

        // Mark messages as read
        $hasChanges = false;
        foreach ($conversation->getMessages() as $msg) {
            if ($msg->getSenderUser()->getId() !== $sessionUserId && !$msg->isRead()) {
                $msg->setIsRead(true);
                $msg->setReadAt(new \DateTimeImmutable());
                $hasChanges = true;
            }
        }
        if ($hasChanges) {
            $em->flush();
        }

        $messages = $em->getRepository(Message::class)->findBy(
            ['conversation' => $conversation],
            ['createdAt' => 'ASC']
        );

        return $this->render('conversation/show.html.twig', [
            'conversation' => $conversation,
            'messages' => $messages,
            'currentUser' => $currentUser,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/conversation/{id}/send', name: 'app_conversation_send', methods: ['POST'])]
    public function send(Conversation $conversation, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $sessionUserId = $request->getSession()->get('user_id');
        if (!$sessionUserId) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        /** @var Users|null $currentUser */
        $currentUser = $em->getRepository(Users::class)->find($sessionUserId);
        $isAdmin = $currentUser && $currentUser->getRole() === 'ROLE_ADMIN';

        // Authorization check
        $isOwner = $conversation->getOwnerUser()->getId() === $sessionUserId;
        if (!$isOwner && !$isAdmin) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $content = $request->request->get('content');
        if (!$content) {
            return new JsonResponse(['error' => 'Empty content'], 400);
        }

        $message = new Message();
        $message->setConversation($conversation);
        $message->setSenderUser($currentUser);
        $message->setContent($content);
        
        $em->persist($message);
        $em->flush();

        // Trigger Notification for the recipient
        $recipient = $isAdmin ? $conversation->getOwnerUser() : $conversation->getAdminUser();
        if ($recipient) {
            $notification = new Notification();
            $notification->setUserid($recipient);
            $notification->setIsRead(false);
            $notification->setCreatedAt(new \DateTime());
            $notification->setMessage('Nouveau message de ' . $currentUser->getUsername() . ' (Post: ' . $conversation->getPost()->getTitle() . ')');
            $notification->setType('MESSAGE');
            $notification->setTargetUrl($this->generateUrl('app_conversation_show', ['id' => $conversation->getId()]));
            $em->persist($notification);
            $em->flush();
        }

        return new JsonResponse([
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'sender' => $message->getSenderUser()->getUsername(),
            'isAdmin' => $message->getSenderUser()->getRole() === 'ROLE_ADMIN',
            'createdAt' => $message->getCreatedAt()->format('H:i'),
            'fullDate' => $message->getCreatedAt()->format('d M Y H:i'),
        ]);
    }

    #[Route('/conversation/{id}/since', name: 'app_conversation_since', methods: ['GET'])]
    public function since(Conversation $conversation, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $sessionUserId = $request->getSession()->get('user_id');
        if (!$sessionUserId) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Authorization check
        /** @var Users|null $currentUser */
        $currentUser = $em->getRepository(Users::class)->find($sessionUserId);
        $isAdmin = $currentUser && $currentUser->getRole() === 'ROLE_ADMIN';

        $isOwner = $conversation->getOwnerUser()->getId() === $sessionUserId;
        if (!$isOwner && !$isAdmin) {
            return new JsonResponse(['error' => 'Forbidden'], 403);
        }

        $lastId = $request->query->getInt('lastId', 0);

        /** @var MessageRepository $repo */
        $repo = $em->getRepository(Message::class);

        // Find messages in this conversation with ID > lastId
        $queryBuilder = $repo->createQueryBuilder('m')
            ->where('m.conversation = :conv')
            ->andWhere('m.id > :lastId')
            ->setParameter('conv', $conversation)
            ->setParameter('lastId', $lastId)
            ->orderBy('m.id', 'ASC');

        $newMessages = $queryBuilder->getQuery()->getResult();

        // Mark them as read if they are not from the current user
        $hasChanges = false;
        foreach ($newMessages as $msg) {
            if ($msg->getSenderUser()->getId() !== $sessionUserId && !$msg->isRead()) {
                $msg->setIsRead(true);
                $msg->setReadAt(new \DateTimeImmutable());
                $hasChanges = true;
            }
        }
        if ($hasChanges) {
            $em->flush();
        }

        // Get lastReadId for the current user's messages
        $lastReadMsg = $repo->findOneBy(
            ['conversation' => $conversation, 'senderUser' => $currentUser, 'isRead' => true],
            ['id' => 'DESC']
        );
        $lastReadId = $lastReadMsg ? $lastReadMsg->getId() : 0;

        $messagesData = [];
        foreach ($newMessages as $msg) {
            $messagesData[] = [
                'id' => $msg->getId(),
                'content' => $msg->getContent(),
                'sender' => $msg->getSenderUser()->getUsername(),
                'isAdmin' => $msg->getSenderUser()->getRole() === 'ROLE_ADMIN',
                'createdAt' => $msg->getCreatedAt()->format('H:i'),
                'fullDate' => $msg->getCreatedAt()->format('d M Y H:i'),
                'isMe' => $msg->getSenderUser()->getId() === $sessionUserId
            ];
        }

        return new JsonResponse([
            'messages' => $messagesData,
            'lastReadId' => $lastReadId
        ]);
    }
}
