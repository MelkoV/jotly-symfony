<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\List;

use App\Dto\List\DeleteListItemData;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeleteListItemRequest
{
    public function __construct(
        #[Assert\Positive]
        public int $version,
    ) {
    }

    public function toDto(): DeleteListItemData
    {
        return new DeleteListItemData($this->version);
    }
}
