<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final readonly class PasswordHashSubject implements PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $email,
    ) {
    }

    public function getPassword(): string
    {
        return '';
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
