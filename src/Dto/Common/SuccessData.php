<?php

declare(strict_types=1);

namespace App\Dto\Common;

final readonly class SuccessData
{
    public function __construct(
        public bool $success = true,
    ) {
    }

    /**
     * @return array{success: bool}
     */
    public function toArray(): array
    {
        return ['success' => $this->success];
    }
}
