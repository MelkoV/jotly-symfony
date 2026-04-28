<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use App\Enum\UserDevice;
use App\Enum\UserStatus;

final readonly class CreateUserData
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public UserStatus $status,
        public UserDevice $device,
        public string $deviceId,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        public \DateTimeImmutable $lastLoginAt,
    ) {
    }
}
