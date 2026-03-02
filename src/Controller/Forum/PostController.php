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
use App\Service\ProfanityFilterService;
use Psr\Log\LoggerInterface;
use App\Service\TextGearsService;
use App\Service\SpamDetectionService;

#[Route('/forum')]
// #[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PostController extends AbstractController
{
    #[Route('', name: 'forum_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $query = $request->query->get('q');
        $user = $this->getUser();
        $isAdmin = $user instanceof Users && strtolower(trim((string)$user->getRole())) === 'admin';

        /** @var PostRepository $repo */
        $repo = $entityManager->getRepository(Post::class);
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $request->getSession()->get('user_role') === 'ROLE_ADMIN';

        if ($query) {
            $qb = $repo->createQueryBuilder('p');
            $qb->where('p.title LIKE :query OR p.content LIKE :query')
                ->setParameter('query', '%' . $query . '%');
            
            if ($isAdmin) {
                $qb->andWhere('p.status IN (:statuses)')
                    ->setParameter('statuses', ['published', 'solved', 'pending']);
            } else {
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
                $posts = $repo->findBy(
                    ['status' => ['published', 'solved', 'pending']], 
                    ['pinned' => 'DESC', 'createdAt' => 'DESC']
                );
            } else {
                $posts = $repo->findPublishedPinnedFirst();
            }
        }

        $this->checkModerationNotifications($entityManager);

        if ($request->isXmlHttpRequest() || $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->render('forum/post/_list.html.twig', [
                'posts' => $posts,
            ]);
        }

        return $this->render('forum/post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, ProfanityFilterService $profanity, LoggerInterface $logger, TextGearsService $textGears, SpamDetectionService $spamDetector): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
                $isAdmin = strtolower(trim((string)$user->getRole())) === 'role_admin' || $request->getSession()->get('user_role') === 'ROLE_ADMIN';

            } else {
                $post->setUser(null);
            }

            if ($isAdmin) {
                $post->setStatus('published');
            } else {
                $post->setStatus('pending');
            }

            /** @var UploadedFile|null $imageFile */
            $imageFile = $post->getImageFile();
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
                    $this->addFlash('danger', 'Error uploading image.');
                }
            }

            $pdfFile = $post->getPdfFile();
if ($pdfFile) {
    $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
    $safeFilename = $slugger->slug($originalFilename);
    $newName = $safeFilename . '-' . uniqid() . '.' . $pdfFile->guessExtension();

    try {
        $pdfFile->move(
            $this->getParameter('kernel.project_dir') . '/public/uploads',
            $newName
        );
        $post->setPdfName($newName);
    } catch (FileException $e) {
        $this->addFlash('danger', 'Error uploading PDF.');
    }
}

            // Moderation Pipeline: TextGears (Correction) -> Profanity Filter
            try {
                // 1. Title Moderation
                $title = $post->getTitle();
                $correctedTitle = $textGears->correct($title);
                $checkTitle = $profanity->check($correctedTitle);
                $post->setTitle($checkTitle['filteredText']);

                // 2. Content Moderation
                $content = (string)$post->getContent();
                $isHtml = $content !== strip_tags($content);
                
                if ($isHtml) {
                    $correctedContent = $content; // Skip correction for HTML to avoid breaking tags
                } else {
                    $correctedContent = $textGears->correct(trim($content));
                }

                $checkContent = $profanity->check(strip_tags($correctedContent)); // Check plain text for profanity
                $post->setContent($checkContent['isProfane'] ? $checkContent['filteredText'] : $correctedContent);

                // 3. Aggregate Moderation Status
                $profaneFound = $checkTitle['isProfane'] || $checkContent['isProfane'];
                $totalProfaneWords = ($checkTitle['profaneWords'] ?? 0) + ($checkContent['profaneWords'] ?? 0);
                
                $post->setIsProfane($profaneFound);
                $post->setProfaneWords($totalProfaneWords);
                
                // 4. Grammar Audit (on content)
                $post->setGrammarErrors($textGears->grammarErrorCount($correctedContent));

                // 5. Business Rule: Auto-moderation
                if ($totalProfaneWords >= 3) {
                    $post->setStatus('pending');
                    $logger->info('Post auto-moderated (Pending) due to high profanity: ' . $post->getTitle());
                }

                // 6. AI Spam Detection
                $spamScore = $spamDetector->calculateScore($post->getTitle(), $post->getContent());
                $post->setSpamScore($spamScore);
                $post->setIsSpam($spamScore >= 70);
            } catch (\Throwable $e) {
                $logger->warning('Moderation pipeline failed for post creation: ' . $e->getMessage());
                // Fallback: keep original title/content (already in $post from form)
            }

            $em->persist($post);
            $em->flush(); // Flush first so $post->getId() is available for the notification URL

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
                    $notification->setType('new_post');
                    $notification->setRelatedId($post->getId());
                    $notification->setTargetUrl($this->generateUrl('admin_post_pending_show', ['id' => $post->getId()]));
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

                $em->flush(); // Flush notifications
            }

            if ($post->getStatus() === 'pending') {
                $this->addFlash('success', 'Your post has been sent to the admin for approval (Status: Pending)');
            } else {
                $this->addFlash('success', 'Your post has been successfully published!');
            }

            return $this->redirectToRoute('forum_index');
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
            throw $this->createAccessDeniedException('This post is pending moderation or has been refused.');
        }

        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        $this->checkModerationNotifications($em);

        return $this->render('forum/post/show.html.twig', [
            'post' => $post,
            'comments' => $em->getRepository(Comment::class)->findBy(
                ['post' => $post, 'parentComment' => null, 'status' => ['visible', 'solution']],
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
            throw $this->createAccessDeniedException('Only the creator or admin can lock comments.');
        }

        $post->setCommentLock(!$post->isCommentLocked());
        $em->flush();

        $message = $post->isCommentLocked() ? 'Comment section blocked' : 'Comment section unblocked';
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $em, SluggerInterface $slugger, ProfanityFilterService $profanity, LoggerInterface $logger, TextGearsService $textGears, SpamDetectionService $spamDetector): Response
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
            throw $this->createAccessDeniedException('You are not authorized to edit this post.');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUpdatedAt(new \DateTime());

            // Moderation Pipeline: TextGears (Correction) -> Profanity Filter
            try {
                // 1. Title Moderation
                $title = $post->getTitle();
                $correctedTitle = $textGears->correct($title);
                $checkTitle = $profanity->check($correctedTitle);
                $post->setTitle($checkTitle['filteredText']);

                // 2. Content Moderation
                $content = (string)$post->getContent();
                $isHtml = $content !== strip_tags($content);

                if ($isHtml) {
                    $correctedContent = $content;
                } else {
                    $correctedContent = $textGears->correct(trim($content));
                }

                $checkContent = $profanity->check(strip_tags($correctedContent));
                $post->setContent($checkContent['isProfane'] ? $checkContent['filteredText'] : $correctedContent);

                // 3. Aggregate Moderation Status
                $profaneFound = $checkTitle['isProfane'] || $checkContent['isProfane'];
                $totalProfaneWords = ($checkTitle['profaneWords'] ?? 0) + ($checkContent['profaneWords'] ?? 0);
                
                $post->setIsProfane($profaneFound);
                $post->setProfaneWords($totalProfaneWords);
                
                // 4. Grammar Audit (on content)
                $post->setGrammarErrors($textGears->grammarErrorCount($correctedContent));

                // 5. Business Rule: Auto-moderation
                if ($totalProfaneWords >= 3) {
                    $post->setStatus('pending');
                    $logger->info('Edited post auto-moderated (Pending) due to high profanity: ' . $post->getId());
                }

                // 6. AI Spam Detection
                $spamScore = $spamDetector->calculateScore($post->getTitle(), $post->getContent());
                $post->setSpamScore($spamScore);
                $post->setIsSpam($spamScore >= 70);
            } catch (\Throwable $e) {
                $logger->warning('Moderation pipeline failed for post edit: ' . $e->getMessage());
                // Fallback: keep original title/content
            }

            /** @var UploadedFile|null $imageFile */
            $imageFile = $post->getImageFile();
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
                    $this->addFlash('danger', 'Error uploading image.');
                }
            }

            /** @var UploadedFile|null $pdfFile */
            $pdfFile = $post->getPdfFile();
            if ($pdfFile) {
                if ($post->getPdfName()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $post->getPdfName();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newName = $safeFilename . '-' . uniqid() . '.' . $pdfFile->guessExtension();

                try {
                    $pdfFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads',
                        $newName
                    );
                    $post->setPdfName($newName);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Error uploading PDF.');
                }
            }

            $em->flush();

            $this->addFlash('success', 'The post has been updated.');

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
            throw $this->createAccessDeniedException('You are not authorized to delete this post.');
        }

        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'The post has been deleted.');
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
            throw $this->createAccessDeniedException('Only administrators can pin posts.');
        }

        $post->setPinned(!$post->isPinned());
        $em->flush();

        $this->addFlash('success', $post->isPinned() ? 'Post pinned!' : 'Post unpinned.');
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
            throw $this->createAccessDeniedException('Only the post owner or an admin can mark a solution.');
        }

        $post->setSolution($comment);
        $post->setStatus('solved');
        $post->setIsCommentLocked(true); // Auto-lock comments when solved
        $em->flush();

        $this->addFlash('success', 'Discussion marked as solved and comments locked!');
        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }
    private function checkModerationNotifications(EntityManagerInterface $em): void
    {
        $user = $this->getUser();
        if (!$user instanceof Users) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
            if ($request) {
                $userId = $request->getSession()->get('user_id');
                if ($userId) {
                    $user = $em->getRepository(Users::class)->find($userId);
                }
            }
        }

        if (!$user instanceof Users) {
            return;
        }

        /** @var \App\Repository\PostRepository $repo */
        $repo = $em->getRepository(Post::class);
        $unnotifiedPosts = $repo->findUnnotifiedModerationPosts($user);

        foreach ($unnotifiedPosts as $post) {
            if ($post->getStatus() === 'published') {
                $this->addFlash('post_approved', 'Congratulations! Your post "' . $post->getTitle() . '" has been approved and published.');
            } elseif ($post->getStatus() === 'refused') {
                $this->addFlash('post_refused', 'Sorry, your post "' . $post->getTitle() . '" has been refused. Reason: ' . ($post->getRefusalReason() ?? 'Not specified'));
            }
            $post->setIsModerationNotified(true);
        }

        if (count($unnotifiedPosts) > 0) {
            $em->flush();
        }
    }
}