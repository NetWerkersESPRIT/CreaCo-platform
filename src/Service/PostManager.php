<?php

namespace App\Service;

use App\Entity\Post;

class PostManager
{
    public function validate(Post $post): bool
    {
        // 🔹 Rule 1: title is required
        if (empty($post->getTitle())) {
            throw new \InvalidArgumentException('The title of the post is required.');
        }

        // 🔹 Rule 2: content must be at least 10 characters long
        if (strlen($post->getContent()) < 10) {
            throw new \InvalidArgumentException('The content must be at least 10 characters long.');
        }

        return true;
    }
}
