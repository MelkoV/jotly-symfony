<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\List;

use App\Dto\List\UpdateShareData;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateShareRequest
{
    public function __construct(
        #[Assert\NotNull]
        public bool $is_share_link,
        #[Assert\NotNull]
        public bool $can_edit,
    ) {
    }

    public function toDto(): UpdateShareData
    {
        return new UpdateShareData($this->is_share_link, $this->can_edit);
    }
}
