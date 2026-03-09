<?php

namespace App\Entity\Enum;

enum ProfessionalInteractionHistoryAction: string
{
    case Validated = 'validated';
    case Refused = 'refused';
}
