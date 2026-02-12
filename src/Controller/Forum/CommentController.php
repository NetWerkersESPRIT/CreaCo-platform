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
    #[Route('/forum/{id}/comment/new', name: 'app_comment_new', methods: ['GET', 'POST'])]
    public function new(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        
        if ($post->isCommentLocked() || $post->getStatus() === 'solved') {
            $this->addFlash('warning', 'Les commentaires sont désactivés pour ce post.');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        $currentUser = $this->getUser();
        
        $comment = new Comment();
        $comment->setPost($post);
        if ($currentUser instanceof Users) {
            $comment->setUser($currentUser);
            $comment->setStatus('visible');
        } else {
            $comment->setUser(null);
            $comment->setStatus('pending');
        }
        $comment->setCreatedAt(new \DateTime());
        $comment->setUpdatedAt(new \DateTime());
        $comment->setLikes(0);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($comment);
            $em->flush();
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('forum/comment/new.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    #[Route('/comment/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $comment->setUpdatedAt(new \DateTime());

            $em->flush();

            return $this->redirectToRoute('app_post_show', [
                'id' => $comment->getPost()->getId()
            ]);
        }

        return $this->render('forum/comment/edit.html.twig', [
            'form' => $form,
            'comment' => $comment,
        ]);
    }

    #[Route('/comment/{id}', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $em): Response
    {
        $postId = $comment->getPost()->getId();

        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();
        }

        return $this->redirectToRoute('app_post_show', ['id' => $postId]);
    }
    #[Route('/comment/{id}/hide', name: 'app_comment_toggle_hide', methods: ['POST'])]
    public function toggleHide(Comment $comment, EntityManagerInterface $em): Response
    {
        
        $user = $this->getUser();
        if (!$user) {
             $this->addFlash('danger', 'Veuillez vous connecter.');
             return $this->redirectToRoute('app_post_show', ['id' => $comment->getPost()->getId()]);
        }

        if ($comment->getUser() === $user || $comment->getPost()->getUser() === $user) {
            $newStatus = ($comment->getStatus() === 'hidden') ? 'visible' : 'hidden';
            $comment->setStatus($newStatus);
            $em->flush();
            $this->addFlash('success', 'Le statut du commentaire a été mis à jour.');
        } else {
            $this->addFlash('danger', 'Action non autorisée.');
        }

        return $this->redirectToRoute('app_post_show', ['id' => $comment->getPost()->getId()]);
    }
}
