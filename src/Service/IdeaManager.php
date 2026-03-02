<?php

namespace App\Service;

use App\Entity\Idea;

class IdeaManager
{
    public function validate(Idea $idea): bool
    {
        if (empty($idea->getTitle())) {
            throw new \InvalidArgumentException('Le titre de l\'idée est obligatoire');
        }

        if (empty($idea->getDescription())) {
            throw new \InvalidArgumentException('La description de l\'idée est obligatoire');
        }

        if (empty($idea->getCategory())) {
            throw new \InvalidArgumentException('La catégorie de l\'idée est obligatoire');
        }

        return true;
    }
}
