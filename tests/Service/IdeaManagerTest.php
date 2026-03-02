<?php

namespace App\Tests\Service;

use App\Entity\Idea;
use App\Service\IdeaManager;
use PHPUnit\Framework\TestCase;

class IdeaManagerTest extends TestCase
{
    public function testValidIdea()
    {
        $idea = new Idea();
        $idea->setTitle('Nouvelle Plateforme');
        $idea->setDescription('Une description détaillée');
        $idea->setCategory('Tech');

        $manager = new IdeaManager();
        $this->assertTrue($manager->validate($idea));
    }

    public function testIdeaMissingDescription()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description de l\'idée est obligatoire');

        $idea = new Idea();
        $idea->setTitle('Titre');
        $idea->setCategory('Category');

        $manager = new IdeaManager();
        $manager->validate($idea);
    }
}
