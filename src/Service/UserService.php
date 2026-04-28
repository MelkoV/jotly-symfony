<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\User\UserData;
use App\Repository\UserRepository;

final readonly class UserService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function getProfileByEmail(string $email): ?UserData
    {
        return $this->userRepository->findProfileByEmail($email);
    }
}
