<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use App\Dto\User\UserData;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class AuthUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        public UserData $user,
        private string $passwordHash,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->user->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }
}
