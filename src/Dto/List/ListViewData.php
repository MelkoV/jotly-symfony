<?php

declare(strict_types=1);

namespace App\Dto\List;

final readonly class ListViewData
{
    /**
     * @param list<ListItemData> $items
     */
    public function __construct(
        public ListData $model,
        public array $items,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'model' => $this->model->toArray(),
            'items' => array_map(
                static fn (ListItemData $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}
