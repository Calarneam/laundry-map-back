<?php

namespace App\Entity\Enum;

enum GeolocationStatus: string
{
    case Geolocated = 'geolocated';
    case Pending = 'pending';
    case Refused = 'refused';
}