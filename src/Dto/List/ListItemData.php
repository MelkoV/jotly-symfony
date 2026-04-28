<?php

declare(strict_types=1);

namespace App\Dto\List;

final readonly class ListItemData
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public string $id,
        public string $userName,
        public string $listId,
        public int $version,
        public bool $isCompleted,
        public ?string $name,
        public array $attributes,
        public ?string $description = null,
        public ?string $userAvatar = null,
        public ?string $completedUserName = null,
        public ?string $completedUserAvatar = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_name' => $this->userName,
            'list_id' => $this->listId,
            'version' => $this->version,
            'is_completed' => $this->isCompleted,
            'name' => $this->name,
            'attributes' => $this->attributes,
            'description' => $this->description,
            'user_avatar' => $this->userAvatar,
            'completed_user_name' => $this->completedUserName,
            'completed_user_avatar' => $this->completedUserAvatar,
        ];
    }
}
