<?php

namespace App\Entity;

enum EquipmentType: string
{
    case Washer = 'washer';
    case Dryer = 'dryer';
    case Other = 'other';
}
