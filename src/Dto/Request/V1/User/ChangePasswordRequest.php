<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\User;

use App\Dto\User\ChangePasswordData;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class ChangePasswordRequest
{
    public function __construct(
        #[SerializedName('old_password')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 50)]
        public string $oldPassword,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 50)]
        public string $password,
        #[SerializedName('repeat_password')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 50)]
        public string $repeatPassword,
    ) {
    }

    #[Assert\Callback]
    public function validatePasswords(ExecutionContextInterface $context): void
    {
        if ($this->password === $this->repeatPassword) {
            return;
        }

        $context->buildViolation('security.password_confirmation_error')
            ->setTranslationDomain('messages')
            ->atPath('repeatPassword')
            ->addViolation();
    }

    public function toDto(): ChangePasswordData
    {
        return new ChangePasswordData(
            $this->oldPassword,
            $this->password,
        );
    }
}
