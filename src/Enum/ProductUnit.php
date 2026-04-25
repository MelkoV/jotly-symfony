<?php

declare(strict_types=1);

namespace App\Enum;

enum ProductUnit: string
{
    case Thing = 'thing';
    case Package = 'package';
    case Kg = 'kg';
}
