<?php

declare(strict_types=1);

namespace App\Dto\List;

final readonly class ListPublicInfoData
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public string $ownerName,
        public ?string $ownerAvatar,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'owner_name' => $this->ownerName,
            'owner_avatar' => $this->ownerAvatar,
        ];
    }
}
