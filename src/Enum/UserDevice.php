<?php

declare(strict_types=1);

namespace App\Enum;

enum UserDevice: string
{
    case Web = 'web';
    case Android = 'android';
    case Ios = 'ios';
}
