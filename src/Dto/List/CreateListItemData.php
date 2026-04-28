<?php

declare(strict_types=1);

namespace App\Dto\List;

final readonly class CreateListItemData
{
    public function __construct(
        public string $listId,
        public string $name,
        public bool $isCompleted = false,
        public ?string $priority = null,
        public ?string $description = null,
        public ?string $unit = null,
        public ?string $deadline = null,
        public ?string $price = null,
        public ?string $cost = null,
        public ?string $count = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'priority' => $this->priority,
            'unit' => $this->unit,
            'deadline' => $this->deadline,
            'price' => $this->price,
            'cost' => $this->cost,
            'count' => $this->count,
        ];
    }
}
