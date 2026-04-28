<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Feedback\CreateFeedbackData;

interface FeedbackRepository
{
    public function create(CreateFeedbackData $data): void;
}
