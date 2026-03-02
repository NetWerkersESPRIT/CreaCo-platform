<?php

namespace App\Service;

use App\Entity\Task;

class TaskManager
{
    public function validate(Task $task): bool
    {
        if (empty($task->getTitle())) {
            throw new \InvalidArgumentException('Le titre de la tâche est obligatoire');
        }

        if (empty($task->getState())) {
            throw new \InvalidArgumentException('Le statut de la tâche est obligatoire');
        }

        if ($task->getTimeLimit() !== null && $task->getTimeLimit() < new \DateTime()) {
            throw new \InvalidArgumentException('La date limite ne peut pas être dans le passé');
        }

        return true;
    }
}
