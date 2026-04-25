<?php

namespace App\Enum;

enum JwtTokenType: string
{
    case Temporary = 'temporary';
    case Refresh = 'refresh';
}
