<?php

namespace App\Entity\Enum;

enum ProfessionalStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Refused = 'refused';
}
