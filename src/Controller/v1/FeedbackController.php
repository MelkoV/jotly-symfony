<?php

declare(strict_types=1);

namespace App\Controller\v1;

use App\Dto\Request\V1\Feedback\CreateFeedbackRequest;
use App\Service\FeedbackService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class FeedbackController extends AbstractController
{
    public function __construct(
        private readonly FeedbackService $feedbackService,
    ) {
    }

    #[Route('/v1/feedback', name: 'api_v1_feedback_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create feedback message',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'message'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Anton'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'anton@example.com'),
                    new OA\Property(property: 'message', type: 'string', example: 'Please add dark mode to the mobile app.'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Feedback created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function create(#[MapRequestPayload] CreateFeedbackRequest $request): JsonResponse
    {
        $result = $this->feedbackService->create($request->toDto());

        return $this->json($result->toArray(), Response::HTTP_CREATED);
    }
}
