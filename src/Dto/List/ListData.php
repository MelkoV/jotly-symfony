<?php

declare(strict_types=1);

namespace App\Dto\List;

use App\Enum\ListType;

final readonly class ListData
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $name,
        public bool $isTemplate,
        public ListType $type,
        public string $ownerName,
        public \DateTimeImmutable $touchedAt,
        public bool $canEdit,
        public ?string $ownerAvatar = null,
        public ?string $description = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->ownerId,
            'name' => $this->name,
            'is_template' => $this->isTemplate,
            'type' => $this->type->value,
            'owner_name' => $this->ownerName,
            'touched_at' => $this->touchedAt->format('Y-m-d H:i:s'),
            'can_edit' => $this->canEdit,
            'owner_avatar' => $this->ownerAvatar,
            'description' => $this->description,
        ];
    }
}
