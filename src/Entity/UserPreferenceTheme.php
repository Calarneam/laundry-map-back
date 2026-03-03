<?php

namespace App\Entity;

enum UserPreferenceTheme: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';
}
