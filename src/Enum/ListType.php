<?php

declare(strict_types=1);

namespace App\Enum;

enum ListType: string
{
    /** Список покупок */
    case Shopping = 'shopping';
    /** Список дел/задач */
    case Todo = 'todo';
    /** Список желаний */
    case Wishlist = 'wishlist';
}
