<?php

namespace App\Entity;

enum ReportReason: string
{
    case AbusiveLanguage = 'abusive_language';
    case Spam = 'spam';
    case UnsolicitedAdvertising = 'unsolicited_advertising';
}
