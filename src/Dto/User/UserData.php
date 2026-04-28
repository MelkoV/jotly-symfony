<?php

declare(strict_types=1);

namespace App\Dto\User;

use App\Enum\UserStatus;

final readonly class UserData
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public UserStatus $status,
        public ?string $avatar,
    ) {
    }

    /**
     * @return array{id: string, name: string, email: string, status: string, avatar: ?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status->value,
            'avatar' => $this->avatar,
        ];
    }
}
