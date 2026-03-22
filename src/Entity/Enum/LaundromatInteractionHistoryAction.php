<?php

namespace App\Entity\Enum;

enum LaundromatInteractionHistoryAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
}
