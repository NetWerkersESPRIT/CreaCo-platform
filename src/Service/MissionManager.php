<?php

namespace App\Service;

use App\Entity\Mission;

class MissionManager
{
    public function validate(Mission $mission): bool
    {
        if (empty($mission->getTitle())) {
            throw new \InvalidArgumentException('Le titre de la mission est obligatoire');
        }

        if (empty($mission->getState())) {
            throw new \InvalidArgumentException('Le statut de la mission est obligatoire');
        }

        if ($mission->getMissionDate() !== null && $mission->getCreatedAt() !== null) {
            if ($mission->getMissionDate() < $mission->getCreatedAt()) {
                throw new \InvalidArgumentException('La date de la mission ne peut pas être antérieure à sa date de création');
            }
        }

        return true;
    }
}
