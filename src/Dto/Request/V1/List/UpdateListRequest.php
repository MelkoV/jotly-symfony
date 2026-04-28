<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\List;

use App\Dto\List\UpdateListData;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateListRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $name,
        #[Assert\Length(max: 250)]
        public ?string $description = null,
    ) {
    }

    public function toDto(): UpdateListData
    {
        return new UpdateListData(
            trim($this->name),
            null === $this->description ? null : trim($this->description),
        );
    }
}
