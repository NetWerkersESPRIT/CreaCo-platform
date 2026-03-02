<?php

namespace App\Service;

use App\Entity\Event;

class EventManager
{
    public function validate(Event $event): bool
    {
        if (empty($event->getName()) || strlen($event->getName()) < 3) {
            throw new \InvalidArgumentException('Le nom de l\'événement est obligatoire et doit faire au moins 3 caractères');
        }

        if (empty($event->getType())) {
            throw new \InvalidArgumentException('Le type d\'événement est obligatoire');
        }

        if ($event->getCapacity() !== null && $event->getCapacity() <= 0) {
            throw new \InvalidArgumentException('La capacité doit être un nombre positif');
        }

        return true;
    }
}
