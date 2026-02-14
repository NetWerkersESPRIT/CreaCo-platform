<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Notification;
use App\Entity\Users;
use App\Repository\PostRepository;
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

        return $this->render('admin/post/pending.html.twig', [
            'posts' => $postRepository->findPending(),
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

        return $this->render('admin/post/show.html.twig', [
            'post' => $post,
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
        $this->addFlash('success', 'Post approved successfully.');

        return $this->redirectToRoute('admin_post_pending');
    }

    #[Route('/{id}/refuse', name: 'admin_post_refuse', methods: ['POST'])]
    public function refuse(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_auth');
        }

        $reason = $request->request->get('refusalReason');
        $post->setStatus('refused');
        $post->setRefusalReason($reason);
        $post->setUpdatedAt(new \DateTime());

        // Notify author
        if ($post->getUser()) {
            $notification = new Notification();
            $notification->setMessage('Your post "' . $post->getTitle() . '" has been refused: ' . $reason);
            $notification->setIsRead(false);
            $notification->setCreatedAt(new \DateTime());
            $notification->setUserId($post->getUser());
            // Redirect to their own post to see the reason
            $notification->setTargetUrl($this->generateUrl('app_post_show', ['id' => $post->getId()]));
            $em->persist($notification);
        }

        $em->flush();
        $this->addFlash('danger', 'Post refused and author notified.');

        return $this->redirectToRoute('admin_post_pending');
    }

    public function countPending(PostRepository $repo): Response
    {
        return new Response((string)$repo->countPending());
    }
}
