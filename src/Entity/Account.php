<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UserDevice;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'accounts')]
#[ORM\UniqueConstraint(name: 'uniq_accounts_user_device_device_id', columns: ['user_id', 'device', 'device_id'])]
class Account
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'accounts')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(length: 20, enumType: UserDevice::class)]
    private UserDevice $device;

    #[ORM\Column(length: 255)]
    private string $deviceId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $lastLoginAt;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDevice(): UserDevice
    {
        return $this->device;
    }

    public function setDevice(UserDevice $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

    public function setDeviceId(string $deviceId): static
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    public function getLastLoginAt(): \DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }
}
