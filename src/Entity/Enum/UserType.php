<?php

namespace App\Entity\Enum;

/**
 * Type de l'utilisateur connecté (contexte auth).
 */
enum UserType: string
{
    case User = 'user';
    case Pro = 'pro';
    case Admin = 'admin';
}
