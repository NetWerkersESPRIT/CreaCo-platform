<?php

namespace App\Tests\Service;

use App\Entity\Task;
use App\Service\TaskManager;
use PHPUnit\Framework\TestCase;

class TaskManagerTest extends TestCase
{
    public function testValidTask()
    {
        $task = new Task();
        $task->setTitle('Correction de bugs');
        $task->setState('To Do');

        $manager = new TaskManager();
        $this->assertTrue($manager->validate($task));
    }

    public function testTaskPastDeadline()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date limite ne peut pas être dans le passé');

        $task = new Task();
        $task->setTitle('Tâche urgente');
        $task->setState('To Do');
        $task->setTimeLimit(new \DateTime('yesterday'));

        $manager = new TaskManager();
        $manager->validate($task);
    }
}
