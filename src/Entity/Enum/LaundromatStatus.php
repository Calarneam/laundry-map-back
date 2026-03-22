<?php

namespace App\Entity\Enum;

enum LaundromatStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Refused = 'refused';
}
