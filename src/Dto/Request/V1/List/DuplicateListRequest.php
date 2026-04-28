<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\List;

use App\Dto\List\DuplicateListData;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DuplicateListRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $name,
    ) {
    }

    public function toDto(): DuplicateListData
    {
        return new DuplicateListData(trim($this->name));
    }
}
