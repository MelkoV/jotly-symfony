<?php

declare(strict_types=1);

namespace App\Dto\User;

final readonly class ChangePasswordData
{
    public function __construct(
        public string $oldPassword,
        public string $password,
    ) {
    }
}
