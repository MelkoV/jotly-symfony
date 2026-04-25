<?php

declare(strict_types=1);

namespace App\Enum;

enum ListFilterTemplate: string
{
    /** Шаблон */
    case Template = 'template';
    /** Рабочее пространство */
    case Worksheet = 'worksheet';
}
