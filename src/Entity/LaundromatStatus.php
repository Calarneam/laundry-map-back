<?php

namespace App\Entity;

enum LaundromatStatus: string
{
    case Pending = 'Pending';
    case Validated = 'Validated';
    case Refused = 'Refused';
}
