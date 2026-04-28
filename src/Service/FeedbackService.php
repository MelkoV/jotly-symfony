<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Common\SuccessData;
use App\Dto\Feedback\CreateFeedbackData;
use App\Repository\FeedbackRepository;

final readonly class FeedbackService
{
    public function __construct(
        private FeedbackRepository $feedbackRepository,
    ) {
    }

    public function create(CreateFeedbackData $data): SuccessData
    {
        $this->feedbackRepository->create($data);

        return new SuccessData();
    }
}
