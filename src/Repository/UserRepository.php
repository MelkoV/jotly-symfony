<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Auth\AuthUser;
use App\Dto\Auth\CreateUserData;
use App\Dto\User\UserData;
use App\Enum\UserDevice;

interface UserRepository
{
    public function existsByEmail(string $email): bool;

    public function createUser(CreateUserData $data): AuthUser;

    public function findAuthUserByEmail(string $email): ?AuthUser;

    public function findProfileByEmail(string $email): ?UserData;

    public function touchAccount(string $userId, UserDevice $device, string $deviceId, \DateTimeImmutable $loggedAt): void;

    public function upgradePasswordByEmail(string $email, string $newHashedPassword): void;
}
