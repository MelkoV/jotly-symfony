<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\HttpFoundation\Cookie;

final readonly class IssuedTokens
{
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
        public Cookie $refreshCookie,
    ) {
    }
}
