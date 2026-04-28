<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\Feedback;

use App\Dto\Feedback\CreateFeedbackData;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateFeedbackRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank]
        public string $message,
    ) {
    }

    public function toDto(): CreateFeedbackData
    {
        return new CreateFeedbackData(
            trim($this->name),
            mb_strtolower(trim($this->email)),
            trim($this->message),
        );
    }
}
