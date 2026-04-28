<?php

declare(strict_types=1);

namespace App\Dto\List;

use App\Enum\ListType;

final readonly class CreateListData
{
    public function __construct(
        public string $name,
        public ListType $type,
        public bool $isTemplate,
        public ?string $description,
    ) {
    }
}
