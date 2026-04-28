<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\Auth;

use App\Dto\Auth\SignInData;
use App\Enum\UserDevice;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SignInRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 255)]
        public string $password,
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

    public function toDto(): SignInData
    {
        return new SignInData(
            mb_strtolower(trim($this->email)),
            $this->password,
            UserDevice::from($this->device),
            trim($this->deviceId),
        );
    }
}
