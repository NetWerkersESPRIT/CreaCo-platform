<?php

namespace App\Tests\Service;

use App\Entity\Mission;
use App\Service\MissionManager;
use PHPUnit\Framework\TestCase;

class MissionManagerTest extends TestCase
{
    public function testValidMission()
    {
        $mission = new Mission();
        $mission->setTitle('Développement API');
        $mission->setState('En cours');

        $manager = new MissionManager();
        $this->assertTrue($manager->validate($mission));
    }

    public function testMissionWithoutTitle()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de la mission est obligatoire');

        $mission = new Mission();
        $mission->setState('En cours');

        $manager = new MissionManager();
        $manager->validate($mission);
    }

    public function testMissionInvalidDate()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de la mission ne peut pas être antérieure à sa date de création');

        $mission = new Mission();
        $mission->setTitle('Mission Test');
        $mission->setState('En cours');
        $mission->setCreatedAt(new \DateTimeImmutable('tomorrow'));
        $mission->setMissionDate(new \DateTime('today'));

        $manager = new MissionManager();
        $manager->validate($mission);
    }
}
