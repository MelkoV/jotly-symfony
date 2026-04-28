<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\Auth;

use App\Dto\Auth\SignUpData;
use App\Enum\UserDevice;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class SignUpRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 255)]
        public string $password,
        #[SerializedName('repeat_password')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 255)]
        public string $repeatPassword,
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [self::class, 'deviceValues'])]
        public string $device,
        #[SerializedName('device_id')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $deviceId,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function deviceValues(): array
    {
        return array_map(static fn (UserDevice $device): string => $device->value, UserDevice::cases());
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

    public function toDto(): SignUpData
    {
        return new SignUpData(
            trim($this->name),
            mb_strtolower(trim($this->email)),
            $this->password,
            UserDevice::from($this->device),
            trim($this->deviceId),
        );
    }
}
