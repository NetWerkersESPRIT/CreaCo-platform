<?php

namespace App\Tests\Service;

use App\Entity\Users;
use App\Entity\UserStreakDay;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GamificationServiceTest extends KernelTestCase
{
    private $entityManager;
    private $gamification;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->gamification = $container->get(App\Service\GamificationService::class);

        // start transaction so changes don't persist
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->isOpen()) {
            $this->entityManager->rollback();
            $this->entityManager->close();
        }
        parent::tearDown();
    }

    public function testAwardBadge(): void
    {
        $user = new Users();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        $user->setPassword('dummy');
        $user->setRole('ROLE_USER');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->assertFalse($this->gamification->hasBadge($user, 'foo')); 
        $awarded = $this->gamification->awardBadge($user, 'foo', ['foo' => 'bar']);
        $this->assertTrue($awarded);
        $this->assertTrue($this->gamification->hasBadge($user, 'foo'));

        $badges = $this->gamification->getUserBadges($user);
        $this->assertCount(1, $badges);
        $this->assertSame('foo', $badges[0]['code']);
    }

    public function testMonthlyStreak(): void
    {
        $user = new Users();
        $user->setUsername('streakuser');
        $user->setEmail('streak@example.com');
        $user->setPassword('dummy');
        $user->setRole('ROLE_USER');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // simulate streak days covering January 2026 (31 days)
        for ($d = 1; $d <= 31; $d++) {
            $usd = new UserStreakDay();
            $usd->setUser($user);
            $usd->setDay(new \DateTime("2026-01-" . sprintf('%02d', $d)));
            $this->entityManager->persist($usd);
        }
        $this->entityManager->flush();

        $awarded = $this->gamification->awardMonthlyStreakBadges(2026, 1);
        $this->assertContains($user->getId(), $awarded);
    }
}
