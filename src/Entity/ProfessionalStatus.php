<?php

namespace App\Entity;

enum ProfessionalStatus: string
{
    case Pending = 'Pending';
    case Refused = 'Refused';
}
