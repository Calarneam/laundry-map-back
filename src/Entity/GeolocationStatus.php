<?php

namespace App\Entity;

enum GeolocationStatus: string
{
    case Geolocated = 'Geolocated';
    case GeolocationError = 'Geolocation_error';
    case Pending = 'Pending';
}
