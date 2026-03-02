<?php
// tests/Service/PostManagerTest.php

namespace App\Tests\Service;

use App\Entity\Post;
use App\Service\PostManager;
use PHPUnit\Framework\TestCase;

class PostManagerTest extends TestCase
{
    public function testValidPost()
    {
        $post = new Post();
        $post->setTitle('My First Post');
        $post->setContent('This is a valid post content with more than 10 characters.');
        
        $manager = new PostManager();
        
        $this->assertTrue($manager->validate($post));
    }
    
    public function testPostWithoutTitle()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The title of the post is required.');
        
        $post = new Post();
        $post->setContent('This is a valid post content with more than 10 characters.');
        
        $manager = new PostManager();
        $manager->validate($post);
    }
    
    public function testPostWithTooShortContent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The content must be at least 10 characters long.');
        
        $post = new Post();
        $post->setTitle('My Post');
        $post->setContent('Short');
        
        $manager = new PostManager();
        $manager->validate($post);
    }
    
    // Optional: Test with empty content
    public function testPostWithEmptyContent()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $post = new Post();
        $post->setTitle('My Post');
        $post->setContent('');
        
        $manager = new PostManager();
        $manager->validate($post);
    }
}