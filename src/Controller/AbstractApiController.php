<?php

namespace App\Controller;

use App\Entity\Administrator;
use App\Entity\Enum\UserType;
use App\Entity\Professional;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Contrôleur de base pour l'API.
 */
abstract class AbstractApiController extends AbstractController
{
    protected function getProfessional(): Professional
    {
        $user = parent::getUser();
        if (!$user instanceof User || $user->getRoles() !== ['ROLE_PRO']) {
            throw new \Exception('Professional not found');
        }
        
        return $user->getProfessional();
    }
}
