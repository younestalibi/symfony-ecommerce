<?php

namespace App\Enum;

enum CartStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
}
