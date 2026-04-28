<?php

declare(strict_types=1);

namespace App\Security;

use App\Dto\Auth\AuthUser;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private TranslatorInterface $translator,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findAuthUserByEmail($identifier);

        if (null !== $user) {
            return $user;
        }

        $exception = new UserNotFoundException($this->translator->trans(
            'security.user_not_found',
            ['%email%' => $identifier],
        ));
        $exception->setUserIdentifier($identifier);

        throw $exception;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof AuthUser) {
            throw new UnsupportedUserException($this->translator->trans(
                'security.invalid_user_class',
                ['%class%' => $user::class],
            ));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return AuthUser::class === $class || is_subclass_of($class, AuthUser::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof AuthUser) {
            return;
        }

        $this->userRepository->upgradePasswordByEmail($user->getUserIdentifier(), $newHashedPassword);
    }
}
