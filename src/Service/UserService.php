<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Common\SuccessData;
use App\Dto\User\ChangePasswordData;
use App\Dto\User\UpdateProfileData;
use App\Dto\User\UserData;
use App\Exception\UserException;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TranslatorInterface $translator,
    ) {
    }

    public function getProfileByEmail(string $email): ?UserData
    {
        return $this->userRepository->findProfileByEmail($email);
    }

    public function updateProfile(string $email, UpdateProfileData $data): ?UserData
    {
        return $this->userRepository->updateNameByEmail($email, $data->name);
    }

    public function changePassword(string $email, ChangePasswordData $data): SuccessData
    {
        $authUser = $this->userRepository->findAuthUserByEmail($email);

        if (null === $authUser || !$this->passwordHasher->isPasswordValid($authUser, $data->oldPassword)) {
            throw new UserException(
                $this->translator->trans('security.current_password_is_incorrect'),
                'old_password',
            );
        }

        $newHashedPassword = $this->passwordHasher->hashPassword($authUser, $data->password);
        $this->userRepository->upgradePasswordByEmail($email, $newHashedPassword);

        return new SuccessData();
    }
}
