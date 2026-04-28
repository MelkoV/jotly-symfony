<?php

declare(strict_types=1);

namespace App\Dto\List;

final readonly class DeleteListItemData
{
    public function __construct(
        public int $version,
    ) {
    }
}
