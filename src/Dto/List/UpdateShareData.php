<?php

declare(strict_types=1);

namespace App\Dto\List;

final readonly class UpdateShareData
{
    public function __construct(
        public bool $isShareLink,
        public bool $canEdit,
    ) {
    }
}
