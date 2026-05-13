<?php

namespace App\Controller;

use App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PostRedirectController extends AbstractController
{
    #[Route('/post/{id}', name: 'post_redirect_singular', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function redirectSingularPost(Post $post): Response
    {
        // Redirect from /post/{id} to /forum/{id}
        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()], 301);
    }
}
