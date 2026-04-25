<?php

declare(strict_types=1);

namespace App\Enum;

enum DeleteListType: string
{
    case Left = 'left';
    case Delete = 'delete';
}
