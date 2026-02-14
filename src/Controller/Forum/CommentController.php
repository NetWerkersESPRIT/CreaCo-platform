<?php

namespace App\Controller\Forum;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Users;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractController
{
    private function getCurrentUser(Request $request, EntityManagerInterface $em): ?Users
    {
        $user = $this->getUser();
        $session = $request->getSession();
        $userId = $session->get('user_id');
        
        if (!$user && $userId) {
            return $em->getRepository(Users::class)->find($userId);
        }
        
        return $user instanceof Users ? $user : null;
    }

    private function isAdmin(Request $request): bool
    {
        return $this->isGranted('ROLE_ADMIN') || $request->getSession()->get('user_role') === 'ROLE_ADMIN';
    }
    #[Route('/forum/{id}/comment/new', name: 'app_comment_new', methods: ['POST'])]
    public function new(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        if ($post->isCommentLocked() || $post->getStatus() === 'solved') {
            $message = $post->getStatus() === 'solved' 
                ? 'This post is solved and comments are closed' 
                : 'The comment section is blocked';
            $this->addFlash('warning', $message);
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        $user = $this->getCurrentUser($request, $em);
        $comment = new Comment();
        $comment->setPost($post);
        $comment->setUser($user);
        $comment->setCreatedAt(new \DateTime());
        $comment->setUpdatedAt(new \DateTime());
        $comment->setLikes(0);
        $comment->setStatus('visible');

        $body = $request->request->get('body');
        $parentId = $request->request->get('parent_id');

        if (!$body) {
            $this->addFlash('danger', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        $comment->setBody($body);

        if ($parentId) {
            $parent = $em->getRepository(Comment::class)->find($parentId);
            if ($parent) {
                $comment->setParentComment($parent);
            }
        }

        $em->persist($comment);
        $em->flush();

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }

    #[Route('/comment/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $em): Response
    {
        $user = $this->getCurrentUser($request, $em);
        $isOwner = $user && $comment->getUser() === $user;

        if (!$isOwner) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres commentaires.');
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUpdatedAt(new \DateTime());
            $em->flush();
            return $this->redirectToRoute('app_post_show', ['id' => $comment->getPost()->getId()]);
        }

        return $this->render('forum/comment/edit.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
        ]);
    }

    #[Route('/comment/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $em): Response
    {
        $user = $this->getCurrentUser($request, $em);
        $isAdmin = $this->isAdmin($request);
        $isOwner = $user && $comment->getUser() === $user;

        if (!$isAdmin && !$isOwner) {
            throw $this->createAccessDeniedException('Non autorisé.');
        }

        $postId = $comment->getPost()->getId();

        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Commentaire supprimé.');
        }

        return $this->redirectToRoute('app_post_show', ['id' => $postId]);
    }
}
