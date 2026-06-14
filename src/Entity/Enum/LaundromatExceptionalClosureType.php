<?php

namespace App\Entity\Enum;

enum LaundromatExceptionalClosureType: string
{
    case FullClosure = 'full_closure';
    case ModifiedHours = 'modified_hours';
}
