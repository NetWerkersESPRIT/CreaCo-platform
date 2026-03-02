<?php

namespace App\Tests\Service;

use App\Entity\Users;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testValidUser()
    {
        $user = new Users();
        $user->setUsername('AnasCh');
        $user->setEmail('anas@example.com');
        $user->setPoints(100);

        $manager = new UserManager();
        $this->assertTrue($manager->validate($user));
    }

    public function testUserWithShortUsername()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom d\'utilisateur est obligatoire et doit faire au moins 4 caractères');

        $user = new Users();
        $user->setUsername('An');
        $user->setEmail('anas@example.com');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithInvalidEmail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email invalide');

        $user = new Users();
        $user->setUsername('AnasCh');
        $user->setEmail('invalid-email');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithNegativePoints()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les points ne peuvent pas être négatifs');

        $user = new Users();
        $user->setUsername('AnasCh');
        $user->setEmail('anas@example.com');
        $user->setPoints(-10);

        $manager = new UserManager();
        $manager->validate($user);
    }
}
