<?php

namespace App\Entity\Enum;

enum Report: string
{
    case Spam = 'spam';
    case HateSpeech = 'hate_speech';
    case AbusiveLanguage = 'abusive_language';
    case IllegalContent = 'illegal_content';
    case Other = 'other';
}
