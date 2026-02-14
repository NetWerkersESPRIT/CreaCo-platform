<?php

namespace App\Controller\Forum;

use App\Entity\Post;
use App\Entity\Users;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Comment;
use App\Form\CommentType;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/forum')]
// #[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PostController extends AbstractController
{
    #[Route('', name: 'forum_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $request->query->get('q');
        $user = $this->getUser();
        $isAdmin = $user instanceof Users && strtolower(trim($user->getRole())) === 'admin';

        $repo = $entityManager->getRepository(Post::class);
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $request->getSession()->get('user_role') === 'ROLE_ADMIN';

        if ($query) {
            $qb = $repo->createQueryBuilder('p');
            $qb->where('p.title LIKE :query OR p.content LIKE :query')
                ->setParameter('query', '%' . $query . '%');
            
            if (!$isAdmin) {
                $qb->andWhere('p.status IN (:statuses)')
                    ->setParameter('statuses', ['published', 'solved']);
            }
            
            // Apply pinning logic even in search if possible, or just date
            $posts = $qb->orderBy('p.pinned', 'DESC')
                       ->addOrderBy('p.createdAt', 'DESC')
                       ->getQuery()
                       ->getResult();
        } else {
            if ($isAdmin) {
                $posts = $repo->findBy([], ['pinned' => 'DESC', 'createdAt' => 'DESC']);
            } else {
                $posts = $repo->findPublishedPinnedFirst();
            }
        }

        return $this->render('forum/post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $now = new \DateTime();
            $post->setCreatedAt($now);
            $post->setUpdatedAt($now);


            $user = $this->getUser();
            if (!$user) {
                $userId = $request->getSession()->get('user_id');
                if ($userId) {
                    $user = $em->getRepository(Users::class)->find($userId);
                }
            }

            $isAdmin = false;
            if ($user instanceof Users) {
                $post->setUser($user);
                $isAdmin = strtolower(trim($user->getRole())) === 'role_admin' || $request->getSession()->get('user_role') === 'ROLE_ADMIN';
            } else {
                $post->setUser(null);
            }

            if ($isAdmin) {
                $post->setStatus('published');
            } else {
                $post->setStatus('pending');
            }

            if ($form->isValid()) {
                /** @var UploadedFile $imageFile */
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads',
                            $newFilename
                        );
                        $post->setImageName($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('danger', 'Erreur lors de l’upload de l’image.');
                    }
                }

                /** @var UploadedFile $pdfFile */
                $pdfFile = $form->get('pdfFile')->getData();
                if ($pdfFile) {
                    $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $pdfFile->guessExtension();

                    try {
                        $pdfFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads',
                            $newFilename
                        );
                        $post->setPdfName($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('danger', 'Erreur lors de l’upload du PDF.');
                    }
                }

                $em->persist($post);

                // Notify Admins & Author
                if ($post->getStatus() === 'pending') {
                    // Admin Notifications
                    $admins = $em->getRepository(Users::class)->findBy(['role' => ['ROLE_ADMIN']]);
                    foreach ($admins as $admin) {
                        $notification = new \App\Entity\Notification();
                        $notification->setMessage('New post pending approval: ' . $post->getTitle());
                        $notification->setIsRead(false);
                        $notification->setCreatedAt(new \DateTime());
                        $notification->setUserId($admin);
                        $em->persist($notification);
                    }

                    // Author Notification
                    if ($user instanceof Users) {
                        $authorNotif = new \App\Entity\Notification();
                        $authorNotif->setMessage('Your post is waiting for the admin to accept it.');
                        $authorNotif->setIsRead(false);
                        $authorNotif->setCreatedAt(new \DateTime());
                        $authorNotif->setUserId($user);
                        $em->persist($authorNotif);
                    }
                }

                $em->flush();

                if ($post->getStatus() === 'pending') {
                    $this->addFlash('success', 'Your post has been sent to the admin for approval (Status: Pending)');
                } else {
                    $this->addFlash('success', 'Votre message a été publié avec succès !');
                }

                return $this->redirectToRoute('forum_index');
            }
        }

        return $this->render('forum/post/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_post_show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function show(Request $request, Post $post, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $session = $request->getSession();
        $userId = $session->get('user_id');
        
        if (!$user && $userId) {
            $user = $em->getRepository(\App\Entity\Users::class)->find($userId);
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN') || $session->get('user_role') === 'ROLE_ADMIN';
        $isOwner = $user && $post->getUser() === $user;

        // Block access to pending/refused posts for unauthorized users
        if (!in_array($post->getStatus(), ['published', 'solved']) && !$isAdmin && !$isOwner) {
            throw $this->createAccessDeniedException('Ce post est en attente de modération ou a été refusé.');
        }

        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        return $this->render('forum/post/show.html.twig', [
            'post' => $post,
            'comments' => $em->getRepository(Comment::class)->findBy(
                ['post' => $post, 'parentComment' => null],
                ['createdAt' => 'ASC']
            ),
            'commentForm' => $commentForm->createView(),
        ]);
    }

    #[Route('/{id}/toggle-lock', name: 'app_post_toggle_lock', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleLock(Post $post, EntityManagerInterface $em, Request $request): Response
    {
        $user = $this->getUser();
        $session = $request->getSession();
        $userId = $session->get('user_id');
        
        if (!$user && $userId) {
            $user = $em->getRepository(\App\Entity\Users::class)->find($userId);
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN') || $session->get('user_role') === 'ROLE_ADMIN';
        $isOwner = $user && $post->getUser() === $user;

        if (!$isAdmin && !$isOwner) {
            throw $this->createAccessDeniedException('Seul le créateur ou l’admin peut verrouiller les commentaires.');
        }

        $post->setCommentLock(!$post->isCommentLocked());
        $em->flush();

        $message = $post->isCommentLocked() ? 'Comment section blocked' : 'Comment section unblocked';
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {

        $user = $this->getUser();
        $session = $request->getSession();
        $userId = $session->get('user_id');
        
        if (!$user && $userId) {
            $user = $em->getRepository(\App\Entity\Users::class)->find($userId);
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN') || $session->get('user_role') === 'ROLE_ADMIN';
        $isOwner = $user && $post->getUser() === $user;

        if (!$isAdmin && !$isOwner) {
            throw $this->createAccessDeniedException('Vous n’êtes pas autorisé à modifier ce post.');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUpdatedAt(new \DateTime());

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {

                if ($post->getImageName()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $post->getImageName();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads',
                        $newFilename
                    );
                    $post->setImageName($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l’upload de l’image.');
                }
            }

            /** @var UploadedFile $pdfFile */
            $pdfFile = $form->get('pdfFile')->getData();
            if ($pdfFile) {

                if ($post->getPdfName()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $post->getPdfName();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $pdfFile->guessExtension();

                try {
                    $pdfFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads',
                        $newFilename
                    );
                    $post->setPdfName($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l’upload du PDF.');
                }
            }

            $em->flush();

            $this->addFlash('success', 'Le message a été mis à jour.');

            return $this->redirectToRoute('forum_index');
        }

        return $this->render('forum/post/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_post_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $em): Response
    {

        $user = $this->getUser();
        $session = $request->getSession();
        $userId = $session->get('user_id');
        
        if (!$user && $userId) {
            $user = $em->getRepository(\App\Entity\Users::class)->find($userId);
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN') || $session->get('user_role') === 'ROLE_ADMIN';
        $isOwner = $user && $post->getUser() === $user;

        if (!$isAdmin && !$isOwner) {
            throw $this->createAccessDeniedException('Vous n’êtes pas autorisé à supprimer ce post.');
        }

        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'Le message a été supprimé.');
        }

        return $this->redirectToRoute('forum_index');
    }

    #[Route('/{id}/like', name: 'app_post_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function like(Post $post, EntityManagerInterface $em, Request $request): Response
    {
        $session = $request->getSession();
        $likedPosts = $session->get('liked_posts', []);

        if (!in_array($post->getId(), $likedPosts)) {
            $post->setLikes($post->getLikes() + 1);
            $likedPosts[] = $post->getId();
            $session->set('liked_posts', $likedPosts);
            $em->flush();
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_post_show', ['id' => $post->getId()]));
    }

    #[Route('/{id}/pin', name: 'app_post_pin', methods: ['POST'])]
    public function pin(Post $post, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $request->getSession()->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException('Seuls les administrateurs peuvent épingler des posts.');
        }

        $post->setPinned(!$post->isPinned());
        $em->flush();

        $this->addFlash('success', $post->isPinned() ? 'Post épinglé !' : 'Post désépinglé.');
        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_post_show', ['id' => $post->getId()]));
    }

    #[Route('/comment/{id}/solve', name: 'app_comment_solve', methods: ['POST'])]
    public function solve(Comment $comment, EntityManagerInterface $em, Request $request): Response
    {
        $post = $comment->getPost();
        $user = $this->getUser();
        $session = $request->getSession();
        $userId = $session->get('user_id');
        
        if (!$user && $userId) {
            $user = $em->getRepository(\App\Entity\Users::class)->find($userId);
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN') || $session->get('user_role') === 'ROLE_ADMIN';
        $isOwner = $user && $post->getUser() === $user;

        if (!$isAdmin && !$isOwner) {
            throw $this->createAccessDeniedException('Seul le propriétaire du post ou un admin peut marquer une solution.');
        }

        $post->setSolution($comment);
        $post->setStatus('solved');
        $post->setIsCommentLocked(true); // Auto-lock comments when solved
        $em->flush();

        $this->addFlash('success', 'Discussion marked as solved and comments locked!');
        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }
}
