<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\Post;
use App\Entity\Users;
use App\Repository\PostRepository;
use App\Service\TextGearsService;
use App\Service\ProfanityFilterService;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/posts')]
class PostModerationController extends AbstractController
{
    #[Route('/pending', name: 'admin_post_pending', methods: ['GET'])]
    public function index(Request $request, PostRepository $postRepository): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            $this->addFlash('warning', 'Access restricted to administrators.');
            return $this->redirectToRoute('app_auth');
        }

        $showSpam = $request->query->getBoolean('spam', false);
        
        if ($showSpam) {
            $posts = $postRepository->findBy(['isSpam' => true], ['createdAt' => 'DESC']);
        } else {
            $posts = $postRepository->findPending();
        }

        return $this->render('admin/post/pending.html.twig', [
            'posts' => $posts,
            'showSpam' => $showSpam,
        ]);
    }

    #[Route('/pending/{id}', name: 'admin_post_pending_show', methods: ['GET'])]
    public function show(Post $post, Request $request): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }

        if ($post->getStatus() !== 'pending') {
            $this->addFlash('warning', 'This post is already moderated.');
            return $this->redirectToRoute('admin_post_pending');
        }

        $rejectionForm = $this->createForm(\App\Form\RejectionReasonType::class, null, [
            'action' => $this->generateUrl('admin_post_refuse', ['id' => $post->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('admin/post/show.html.twig', [
            'post' => $post,
            'rejectionForm' => $rejectionForm->createView(),
        ]);
    }

    #[Route('/{id}/approve', name: 'admin_post_approve', methods: ['POST'])]
    public function approve(Post $post, EntityManagerInterface $em, Request $request): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }

        $post->setStatus('published');
        $post->setRefusalReason(null);
        $post->setIsModerationNotified(false);

        // Notify author
        if ($post->getUser()) {
            $notification = new Notification();
            $notification->setMessage('Your post "' . $post->getTitle() . '" has been approved');
            $notification->setIsRead(false);
            $notification->setCreatedAt(new \DateTime());
            $notification->setUserId($post->getUser());
            // Target URL for the notification
            $notification->setTargetUrl($this->generateUrl('app_post_show', ['id' => $post->getId()]));
            $em->persist($notification);
        }

        $em->flush();

        return $this->redirectToRoute('admin_post_pending');
    }

    #[Route('/{id}/refuse', name: 'admin_post_refuse', methods: ['POST'])]
    public function refuse(Post $post, Request $request, EntityManagerInterface $em, TextGearsService $textGears, ProfanityFilterService $profanity, LoggerInterface $logger): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }

        $form = $this->createForm(\App\Form\RejectionReasonType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reason = trim((string)$form->get('rejectionReason')->getData());

            // Moderation Pipeline for Refusal Reason
            try {
                // 1. TextGears Correction (fixed en-US)
                $correctedReason = $textGears->correct($reason);
                
                // 2. Profanity Filtering
                $check = $profanity->check($correctedReason);
                $finalReason = $check['filteredText'] ?? $correctedReason;
                
                $post->setRefusalReason($finalReason);
            } catch (\Throwable $e) {
                $logger->warning('Moderation pipeline failed for refusal reason: ' . $e->getMessage());
                // Fallback: save original trimmed reason
                $post->setRefusalReason($reason);
            }

            $post->setStatus('refused');
            $post->setIsModerationNotified(false);
            $post->setUpdatedAt(new \DateTime());

            // Refusal Discussion: Find or Create Conversation
            $conversation = $post->getConversation();
            if (!$conversation) {
                $conversation = new Conversation();
                $conversation->setPost($post);
                $conversation->setOwnerUser($post->getUser());
                
                // Fetch the current admin user from session
                $sessionUserId = $request->getSession()->get('user_id');
                $adminUser = $em->getRepository(Users::class)->find($sessionUserId);
                $conversation->setAdminUser($adminUser);
                
                $em->persist($conversation);
            }

            // Create initial message with the moderated refusal reason
            $initialMessage = new Message();
            $initialMessage->setConversation($conversation);
            $initialMessage->setSenderUser($conversation->getAdminUser());
            $initialMessage->setContent($post->getRefusalReason());
            $initialMessage->setStatus('visible');
            $em->persist($initialMessage);

            $em->flush();

            // Notify author
            if ($post->getUser()) {
                $notification = new Notification();
                $notification->setMessage('Your post "' . $post->getTitle() . '" has been refused: ' . $post->getRefusalReason());
                $notification->setIsRead(false);
                $notification->setCreatedAt(new \DateTime());
                $notification->setUserId($post->getUser());
                // Point the notification to the refusal discussion
                $notification->setTargetUrl($this->generateUrl('app_conversation_show', ['id' => $conversation->getId()]));
                $em->persist($notification);
                $em->flush(); // Flush the notification itself
            }

            return $this->redirectToRoute('admin_post_pending');
        }

        // If invalid, go back to the show page with errors
        return $this->render('admin/post/show.html.twig', [
            'post' => $post,
            'rejectionForm' => $form->createView(),
        ]);
    }

    public function countPending(PostRepository $repo): Response
    {
        return new Response((string)$repo->countPending());
    }
}
