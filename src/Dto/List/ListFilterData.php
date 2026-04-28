<?php

declare(strict_types=1);

namespace App\Dto\List;

use App\Enum\ListFilterTemplate;
use App\Enum\ListType;

final readonly class ListFilterData
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 100,
        public bool $isOwner = false,
        public ?ListType $type = null,
        public ?ListFilterTemplate $template = null,
        public ?string $text = null,
    ) {
    }
}
