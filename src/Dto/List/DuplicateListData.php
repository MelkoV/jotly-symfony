<?php

declare(strict_types=1);

namespace App\Dto\List;

final readonly class DuplicateListData
{
    public function __construct(
        public string $name,
    ) {
    }
}
