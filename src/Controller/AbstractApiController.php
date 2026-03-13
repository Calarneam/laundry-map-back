<?php

namespace App\Controller;

use App\Entity\Administrator;
use App\Entity\Enum\UserType;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Contrôleur de base pour l'API.
 * Expose une méthode pour connaître le type de l'utilisateur connecté (user, pro, admin).
 */
abstract class AbstractApiController extends AbstractController
{
    /**
     * Retourne le type de l'utilisateur connecté (User, Pro ou Admin).
     * Retourne null si non authentifié.
     */
    protected function getCurrentUserType(): ?UserType
    {
        $user = $this->getUser();
        if ($user === null) {
            return null;
        }

        if ($user instanceof Administrator) {
            return UserType::Admin;
        }

        if ($user instanceof User) {
            return $user->getProfessional() !== null ? UserType::Pro : UserType::User;
        }

        return null;
    }

    /**
     * Vérifie si l'utilisateur connecté est un simple utilisateur (sans compte pro).
     */
    protected function isUser(): bool
    {
        return $this->getCurrentUserType() === UserType::User;
    }

    /**
     * Vérifie si l'utilisateur connecté est un professionnel (user avec Professional).
     */
    protected function isPro(): bool
    {
        return $this->getCurrentUserType() === UserType::Pro;
    }

    /**
     * Vérifie si l'utilisateur connecté est un administrateur.
     */
    protected function isAdmin(): bool
    {
        return $this->getCurrentUserType() === UserType::Admin;
    }
}
