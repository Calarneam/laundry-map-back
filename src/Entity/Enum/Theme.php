<?php

namespace App\Entity\Enum;

enum Theme: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';
}