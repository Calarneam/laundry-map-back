<?php

namespace App\Entity\Enum;

enum WebLinkType: string
{
    case Website = 'website';
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case X = 'x';
    case Linkedin = 'linkedin';
}
