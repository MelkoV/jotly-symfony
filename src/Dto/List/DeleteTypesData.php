<?php

declare(strict_types=1);

namespace App\Dto\List;

final readonly class DeleteTypesData
{
    public function __construct(
        public bool $left,
        public bool $delete,
    ) {
    }

    /**
     * @return array{left: bool, delete: bool}
     */
    public function toArray(): array
    {
        return [
            'left' => $this->left,
            'delete' => $this->delete,
        ];
    }
}
