<?php

declare(strict_types=1);

namespace App\Dto\List;

final readonly class UpdateListData
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {
    }
}
