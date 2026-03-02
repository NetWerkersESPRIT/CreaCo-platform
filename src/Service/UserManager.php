<?php

namespace App\Service;

use App\Entity\Users;

class UserManager
{
    public function validate(Users $user): bool
    {
        if (empty($user->getUsername()) || strlen($user->getUsername()) < 4) {
            throw new \InvalidArgumentException('Le nom d\'utilisateur est obligatoire et doit faire au moins 4 caractères');
        }

        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }

        if ($user->getPoints() < 0) {
            throw new \InvalidArgumentException('Les points ne peuvent pas être négatifs');
        }

        return true;
    }
}
