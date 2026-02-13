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
        $qb = $repo->createQueryBuilder('p');

        if ($query) {
            $qb->where('p.title LIKE :query OR p.content LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if (!$isAdmin) {
            $qb->andWhere('p.status IN (:statuses)')
               ->setParameter('statuses', ['published', 'solved']);
        }

        $posts = $qb->orderBy('p.createdAt', 'DESC')
                    ->getQuery()
                    ->getResult();

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
            $isAdmin = $user instanceof Users && strtolower(trim($user->getRole())) === 'admin';

            if ($isAdmin) {
                $post->setUser($user);
                $post->setStatus('published');
            } else {
                if ($user instanceof Users) {
                    $post->setUser($user);
                } else {
                    $post->setUser(null);
                }
                $post->setStatus('pending');

                // Create notifications for all admins
                $userRepo = $em->getRepository(Users::class);
                $allUsers = $userRepo->findAll();
                foreach ($allUsers as $u) {
                    if (strtolower(trim($u->getRole())) === 'admin') {
                        $notification = new Notification();
                        $notification->setUser($u);
                        $notification->setMessage("Un nouveau post '" . $post->getTitle() . "' est en attente de modération.");
                        $notification->setRelatedPost($post);
                        $em->persist($notification);
                    }
                }
            }

            if ($form->isValid()) {
                /** @var UploadedFile $imageFile */
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

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
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$pdfFile->guessExtension();

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
                $em->flush();

                $this->addFlash('success', 'Votre message a été publié avec succès !');

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
        
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setPost($post);
            
            $parentId = $request->request->get('parent_id');
            if ($parentId) {
                $parentComment = $em->getRepository(Comment::class)->find($parentId);
                if ($parentComment) {
                    $comment->setParentComment($parentComment);
                }
            }

            if ($post->isCommentLocked()) {
                $this->addFlash('warning', 'Les commentaires sont désactivés pour ce post.');
                return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
            }

            $user = $this->getUser();
            if ($user instanceof Users) {
                 $comment->setUser($user);
                 $comment->setStatus('visible');
            } else {
                 $comment->setUser(null);
                 $comment->setStatus('pending');
            }

            $now = new \DateTime();
            $comment->setCreatedAt($now);
            $comment->setUpdatedAt($now);
            $comment->setStatus('visible');
            $comment->setLikes(0);

            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Votre commentaire a été ajouté !');

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        if ($request->isMethod('POST') && $request->request->get('comment_body')) {
            $body = $request->request->get('comment_body');
            $parentId = $request->request->get('parent_id');

            $reply = new Comment();
            $reply->setBody($body);
            $reply->setPost($post);
            
            if ($post->isCommentLocked()) {
                $this->addFlash('warning', 'Les commentaires sont désactivés pour ce post.');
                return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
            }

            $user = $this->getUser();
            if ($user instanceof Users) {
                $reply->setUser($user);
                $reply->setStatus('visible');
            } else {
                $reply->setUser(null);
                $reply->setStatus('pending');
            }

            if ($parentId) {
                $parentComment = $em->getRepository(Comment::class)->find($parentId);
                if ($parentComment) {
                    $reply->setParentComment($parentComment);
                }
            }

            $reply->setCreatedAt(new \DateTime());
            $reply->setStatus('visible');
            $reply->setLikes(0);

            $em->persist($reply);
            $em->flush();

            $this->addFlash('success', 'Votre réponse a été ajoutée !');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        
        $comments = $em->getRepository(Comment::class)->findBy(
            ['post' => $post, 'parentComment' => null],
            ['createdAt' => 'ASC']
        );

        return $this->render('forum/post/show.html.twig', [
            'post' => $post,
            'comments' => $comments, 
            'commentForm' => $commentForm->createView(),
        ]);
    }

    #[Route('/{id}/toggle-lock', name: 'app_post_toggle_lock', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleLock(Post $post, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        $isAdmin = strtolower(trim($user->getRole())) === 'admin';
        $isCreator = ($post->getUser() === $user);

        if (!$isAdmin && !$isCreator) {
            $this->addFlash('danger', 'Action non autorisée. Seul le créateur ou l’admin peut verrouiller les commentaires.');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        $post->setCommentLock(!$post->isCommentLocked());
        $em->flush();

        $message = $post->isCommentLocked() ? 'Les commentaires ont été désactivés.' : 'Les commentaires ont été activés.';
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        
        if ($this->getUser() !== $post->getUser()) {
             $this->addFlash('danger', 'Vous n’êtes pas autorisé à modifier ce post.');
             return $this->redirectToRoute('forum_index');
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
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

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
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pdfFile->guessExtension();

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
        
        if ($this->getUser() !== $post->getUser()) {
             $this->addFlash('danger', 'Vous n’êtes pas autorisé à supprimer ce post.');
             return $this->redirectToRoute('forum_index');
        }

        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'Le message a été supprimé.');
        }

        return $this->redirectToRoute('forum_index');
    }

    #[Route('/{id}/like', name: 'app_post_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function like(Post $post, EntityManagerInterface $em): Response
    {
        $post->setLikes($post->getLikes() + 1);
        $em->flush();

        return $this->redirectToRoute('forum_index');
    }
}
