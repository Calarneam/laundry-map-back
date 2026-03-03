<?php

namespace App\Entity;

enum UserStatus: string
{
    case Pending = 'Pending';
    case Validated = 'Validated';
    case Refused = 'Refused';
    case Banned = 'Banned';
}
