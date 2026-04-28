<?php

declare(strict_types=1);

namespace App\Dto\Auth;

final readonly class RefreshTokenData
{
    public function __construct(
        public ?string $token,
    ) {
    }
}
