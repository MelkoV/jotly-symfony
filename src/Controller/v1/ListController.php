<?php

declare(strict_types=1);

namespace App\Controller\v1;

use App\Dto\Request\V1\List\CreateListRequest;
use App\Dto\Request\V1\List\FilterListsRequest;
use App\Dto\Request\V1\List\UpdateListRequest;
use App\Exception\ListException;
use App\Service\ListService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ListController extends AbstractController
{
    public function __construct(
        private readonly ListService $listService,
    ) {
    }

    #[Route('/v1/lists', name: 'api_v1_lists_index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        summary: 'Get filtered user lists',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'text', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['shopping', 'todo', 'wishlist'])),
            new OA\Parameter(name: 'template', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['template', 'worksheet'])),
            new OA\Parameter(name: 'is_owner', in: 'query', required: true, schema: new OA\Schema(type: 'boolean', default: false)),
            new OA\Parameter(name: 'page', in: 'query', required: true, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: true, schema: new OA\Schema(
                type: 'integer',
                default: 100,
                maximum: 100,
                minimum: 1
            )),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Paginated list collection',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedListsResponse'),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Authentication required'),
        ],
    )]
    public function index(
        #[CurrentUser] UserInterface $user,
        #[MapQueryString] FilterListsRequest $request,
        Request $httpRequest,
    ): JsonResponse {
        $result = $this->listService->getFiltered($user->getUserIdentifier(), $request->toDto());

        return $this->json($result->toArray(
            $httpRequest->getUriForPath('/api/v1/lists'),
            [
                'text' => $request->text,
                'type' => $request->type,
                'template' => $request->template,
                'is_owner' => $request->is_owner ? '1' : '0',
                'per_page' => $request->per_page,
            ],
        ));
    }

    #[Route('/v1/lists', name: 'api_v1_lists_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Post(
        summary: 'Create a new list',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'type', 'is_template'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'test'),
                    new OA\Property(property: 'type', type: 'string', enum: ['shopping', 'todo', 'wishlist'], example: 'shopping'),
                    new OA\Property(property: 'is_template', type: 'boolean', example: false),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Groceries for the weekend'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'List created successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ListSummary'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function create(
        #[CurrentUser] UserInterface $user,
        #[MapRequestPayload] CreateListRequest $request,
    ): JsonResponse {
        $result = $this->listService->create($user->getUserIdentifier(), $request->toDto());

        return $this->json($result->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/v1/lists/{id}', name: 'api_v1_lists_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        summary: 'View a list',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List view',
                content: new OA\JsonContent(ref: '#/components/schemas/ListViewResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'List access error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function show(#[CurrentUser] UserInterface $user, string $id): JsonResponse
    {
        try {
            $result = $this->listService->findById($user->getUserIdentifier(), $id);
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/lists/{id}', name: 'api_v1_lists_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Put(
        summary: 'Update a list',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'test updated'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Updated description'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ListSummary'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation or access error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function update(
        #[CurrentUser] UserInterface $user,
        string $id,
        #[MapRequestPayload] UpdateListRequest $request,
    ): JsonResponse {
        try {
            $result = $this->listService->update($user->getUserIdentifier(), $id, $request->toDto());
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/lists/{id}', name: 'api_v1_lists_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Delete(
        summary: 'Delete a list',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List deleted successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Access error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function delete(#[CurrentUser] UserInterface $user, string $id): JsonResponse
    {
        try {
            $result = $this->listService->delete($user->getUserIdentifier(), $id);
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    private function errorResponse(ListException $exception): JsonResponse
    {
        return $this->json([
            'message' => $exception->getMessage(),
            'errors' => [$exception->getField() => [$exception->getMessage()]],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
