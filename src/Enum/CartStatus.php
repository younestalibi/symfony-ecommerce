<?php

namespace App\Enum;

enum CartStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
}
