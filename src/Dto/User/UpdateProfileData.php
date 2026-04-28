<?php

declare(strict_types=1);

namespace App\Dto\User;

final readonly class UpdateProfileData
{
    public function __construct(
        public string $name,
    ) {
    }
}
