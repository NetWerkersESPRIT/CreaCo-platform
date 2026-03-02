<?php

namespace App\Tests\Service;

use App\Entity\Event;
use App\Service\EventManager;
use PHPUnit\Framework\TestCase;

class EventManagerTest extends TestCase
{
    public function testValidEvent()
    {
        $event = new Event();
        $event->setName('Workshop Symfony');
        $event->setType('Workshop');
        $event->setCapacity(50);

        $manager = new EventManager();
        $this->assertTrue($manager->validate($event));
    }

    public function testEventWithInvalidName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom de l\'événement est obligatoire et doit faire au moins 3 caractères');

        $event = new Event();
        $event->setName('AB');
        $event->setType('Workshop');

        $manager = new EventManager();
        $manager->validate($event);
    }

    public function testEventWithEmptyType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type d\'événement est obligatoire');

        $event = new Event();
        $event->setName('Workshop Symfony');
        $event->setCapacity(50); // type is missing

        $manager = new EventManager();
        $manager->validate($event);
    }

    public function testEventWithInvalidCapacity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité doit être un nombre positif');

        $event = new Event();
        $event->setName('Workshop Symfony');
        $event->setType('Workshop');
        $event->setCapacity(-10);

        $manager = new EventManager();
        $manager->validate($event);
    }
}
