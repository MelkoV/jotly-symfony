<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\User;

use App\Dto\User\UpdateProfileData;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProfileRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $name,
    ) {
    }

    public function toDto(): UpdateProfileData
    {
        return new UpdateProfileData(
            trim($this->name),
        );
    }
}
