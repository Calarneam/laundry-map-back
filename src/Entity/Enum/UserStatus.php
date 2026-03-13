<?php

namespace App\Entity\Enum;

enum UserStatus: string
{
    case Active = 'active';
    case Banned = 'banned';
}
