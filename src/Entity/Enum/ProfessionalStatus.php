<?php

namespace App\Entity\Enum;

enum ProfessionalStatus: string
{
    case Validated = 'validated';
    case Pending = 'pending';
    case Refused = 'refused';
}
