<?php

declare(strict_types=1);

namespace App\Dto\Feedback;

final readonly class CreateFeedbackData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $message,
    ) {
    }
}
