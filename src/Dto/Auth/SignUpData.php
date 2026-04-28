<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use App\Enum\UserDevice;

final readonly class SignUpData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public UserDevice $device,
        public string $deviceId,
    ) {
    }
}
