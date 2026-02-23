<?php

namespace App\Controller\Forum;

use App\Entity\Post;
use App\Entity\PostReaction;
use App\Repository\PostReactionRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PostReactionController extends AbstractController
{
    #[Route('/posts/{id}/react', name: 'app_post_react', methods: ['POST'])]
    public function react(
        Post $post,
        Request $request,
        EntityManagerInterface $em,
        PostReactionRepository $reactionRepo,
        UsersRepository $userRepo
    ): JsonResponse {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return new JsonResponse(['error' => 'You must be logged in to react.'], 401);
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? 'like';

        // Allowed reaction types
        $allowedTypes = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
        if (!in_array($type, $allowedTypes)) {
            return new JsonResponse(['error' => 'Invalid reaction type.'], 400);
        }

        // Check for existing reaction by this user on this post
        $reaction = $reactionRepo->findOneBy(['user' => $user, 'post' => $post]);

        if ($reaction) {
            // If the same type is clicked, remove the reaction (toggle off)
            if ($reaction->getType() === $type) {
                $em->remove($reaction);
                $action = 'removed';
            } else {
                // Otherwise update the type
                $reaction->setType($type);
                $reaction->setCreatedAt(new \DateTimeImmutable());
                $action = 'updated';
            }
        } else {
            // Create new reaction
            $reaction = new PostReaction();
            $reaction->setUser($user);
            $reaction->setPost($post);
            $reaction->setType($type);
            $em->persist($reaction);
            $action = 'created';
        }

        $em->flush();

        // Get updated counts
        $counts = $reactionRepo->countByPostAndType($post->getId());

        return new JsonResponse([
            'success' => true,
            'action' => $action,
            'counts' => $counts,
            'user_reaction' => $action === 'removed' ? null : $type
        ]);
    }
}
