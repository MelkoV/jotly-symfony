<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use Symfony\Component\HttpFoundation\Cookie;

final readonly class AuthResult
{
    /**
     * @param array<string, mixed>|null $payload
     */
    public function __construct(
        public ?array $payload = null,
        public int $statusCode = 200,
        public ?Cookie $cookie = null,
    ) {
    }
}
