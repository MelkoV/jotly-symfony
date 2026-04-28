<?php

declare(strict_types=1);

namespace App\Controller\v1;

use App\Dto\Request\V1\List\CreateListRequest;
use App\Dto\Request\V1\List\DuplicateListRequest;
use App\Dto\Request\V1\List\CreateListItemRequest;
use App\Dto\Request\V1\List\DeleteListItemRequest;
use App\Dto\Request\V1\List\FilterListsRequest;
use App\Dto\Request\V1\List\UpdateListRequest;
use App\Dto\Request\V1\List\UpdateListItemRequest;
use App\Dto\Request\V1\List\UpdateShareRequest;
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

    #[Route('/v1/lists/delete-types/{id}', name: 'api_v1_lists_delete_types', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        summary: 'Get available delete actions for a list',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Delete action options',
                content: new OA\JsonContent(ref: '#/components/schemas/DeleteTypesResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Access error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function deleteTypes(#[CurrentUser] UserInterface $user, string $id): JsonResponse
    {
        try {
            $result = $this->listService->getDeleteTypes($user->getUserIdentifier(), $id);
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/lists/left/{id}', name: 'api_v1_lists_left', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Delete(
        summary: 'Leave a shared list',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List left successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Access error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function left(#[CurrentUser] UserInterface $user, string $id): JsonResponse
    {
        try {
            $result = $this->listService->leftUser($user->getUserIdentifier(), $id);
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/lists/share/{id}', name: 'api_v1_lists_share', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        summary: 'Get share settings for an owned list',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Current share settings',
                content: new OA\JsonContent(ref: '#/components/schemas/ShareResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Access error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function share(#[CurrentUser] UserInterface $user, string $id): JsonResponse
    {
        try {
            $result = $this->listService->getShareData($user->getUserIdentifier(), $id);
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/lists/share/{id}', name: 'api_v1_lists_share_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Put(
        summary: 'Update share settings for an owned list',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateShareRequest')),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Share settings updated successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ShareResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation or access error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function updateShare(
        #[CurrentUser] UserInterface $user,
        string $id,
        #[MapRequestPayload] UpdateShareRequest $request,
    ): JsonResponse {
        try {
            $result = $this->listService->updateShareData($user->getUserIdentifier(), $id, $request->toDto());
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/lists/join/{id}', name: 'api_v1_lists_join', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Post(
        summary: 'Join a list by shared link',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List joined successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ListSummary'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Link access error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function join(#[CurrentUser] UserInterface $user, string $id): JsonResponse
    {
        try {
            $result = $this->listService->joinByLink($user->getUserIdentifier(), $id);
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/lists/info/{url}', name: 'api_v1_lists_info', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get public list info by short url',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Public list info',
                content: new OA\JsonContent(ref: '#/components/schemas/ListPublicInfoResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'List not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function info(string $url): JsonResponse
    {
        try {
            $result = $this->listService->findPublicInfoByShortUrl($url);
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/lists/copy/{id}', name: 'api_v1_lists_copy', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Post(
        summary: 'Copy a list',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Copied list'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List copied successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ListPublicInfoResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation or access error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function copy(
        #[CurrentUser] UserInterface $user,
        string $id,
        #[MapRequestPayload] DuplicateListRequest $request,
    ): JsonResponse {
        try {
            $result = $this->listService->copy($user->getUserIdentifier(), $id, $request->toDto());
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/lists/create-from-template/{id}', name: 'api_v1_lists_create_from_template', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Post(
        summary: 'Create a list from template',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'New workspace'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List created from template successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ListPublicInfoResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation or access error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function createFromTemplate(
        #[CurrentUser] UserInterface $user,
        string $id,
        #[MapRequestPayload] DuplicateListRequest $request,
    ): JsonResponse {
        try {
            $result = $this->listService->createFromTemplate($user->getUserIdentifier(), $id, $request->toDto());
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/list-items', name: 'api_v1_list_items_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Post(
        summary: 'Create a new list item',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateListItemRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'List item created successfully', content: new OA\JsonContent(ref: '#/components/schemas/ListItem')),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation or access error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function createItem(
        #[CurrentUser] UserInterface $user,
        #[MapRequestPayload] CreateListItemRequest $request,
    ): JsonResponse {
        try {
            $result = $this->listService->createListItem($user->getUserIdentifier(), $request->toDto());
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/v1/list-items/{id}', name: 'api_v1_list_items_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Put(
        summary: 'Update a list item',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateListItemRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'List item updated successfully', content: new OA\JsonContent(ref: '#/components/schemas/ListItem')),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation or domain error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function updateItem(
        #[CurrentUser] UserInterface $user,
        string $id,
        #[MapRequestPayload] UpdateListItemRequest $request,
    ): JsonResponse {
        try {
            $result = $this->listService->updateListItem($user->getUserIdentifier(), $id, $request->toDto());
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/list-items/{id}', name: 'api_v1_list_items_delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Delete(
        summary: 'Delete a list item',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'version', in: 'query', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'List item deleted successfully', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation or domain error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function deleteItem(
        #[CurrentUser] UserInterface $user,
        string $id,
        #[MapQueryString] DeleteListItemRequest $request,
    ): JsonResponse {
        try {
            $result = $this->listService->deleteListItem($user->getUserIdentifier(), $id, $request->toDto());
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/list-items/complete/{id}', name: 'api_v1_list_items_complete', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Put(
        summary: 'Complete a list item',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateListItemRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'List item completed successfully', content: new OA\JsonContent(ref: '#/components/schemas/ListItem')),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation or domain error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function completeItem(
        #[CurrentUser] UserInterface $user,
        string $id,
        #[MapRequestPayload] UpdateListItemRequest $request,
    ): JsonResponse {
        try {
            $result = $this->listService->completeListItem($user->getUserIdentifier(), $id, $request->toDto());
        } catch (ListException $e) {
            return $this->errorResponse($e);
        }

        return $this->json($result->toArray());
    }

    #[Route('/v1/list-items/uncomplete/{id}', name: 'api_v1_list_items_uncomplete', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Put(
        summary: 'Restore a completed list item',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateListItemRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'List item restored successfully', content: new OA\JsonContent(ref: '#/components/schemas/ListItem')),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation or domain error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    public function uncompleteItem(
        #[CurrentUser] UserInterface $user,
        string $id,
        #[MapRequestPayload] UpdateListItemRequest $request,
    ): JsonResponse {
        try {
            $result = $this->listService->uncompleteListItem($user->getUserIdentifier(), $id, $request->toDto());
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
