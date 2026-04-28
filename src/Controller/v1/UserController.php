<?php

declare(strict_types=1);

namespace App\Controller\v1;

use App\Dto\Auth\RefreshTokenData;
use App\Dto\Request\V1\Auth\SignInRequest;
use App\Dto\Request\V1\Auth\SignUpRequest;
use App\Exception\UserAuthException;
use App\Service\AuthService;
use App\Service\UserService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly UserService $userService,
        #[Autowire('%env(JWT_REFRESH_COOKIE_NAME)%')]
        private readonly string $refreshCookieName,
    ) {
    }

    #[Route('/v1/user/sign-up', name: 'api_v1_user_sign_up', methods: ['POST'])]
    #[OA\Post(
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'repeat_password', 'device', 'device_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Anton'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'anton@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'repeat_password', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'device', type: 'string', enum: ['web', 'android', 'ios'], example: 'web'),
                    new OA\Property(property: 'device_id', type: 'string', example: 'browser-123'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'User registered successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation or authentication error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function signUp(#[MapRequestPayload] SignUpRequest $request): JsonResponse
    {
        try {
            return $this->toJsonResponse($this->authService->signUp($request->toDto()));
        } catch (UserAuthException $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    #[Route('/v1/user/sign-in', name: 'api_v1_user_sign_in', methods: ['POST'])]
    #[OA\Post(
        summary: 'Sign in a user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'device', 'device_id'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'anton@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'device', type: 'string', enum: ['web', 'android', 'ios'], example: 'web'),
                    new OA\Property(property: 'device_id', type: 'string', example: 'browser-123'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'User signed in successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse'),
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Validation or authentication error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse'),
            ),
        ],
    )]
    public function signIn(#[MapRequestPayload] SignInRequest $request): JsonResponse
    {
        try {
            return $this->toJsonResponse($this->authService->signIn($request->toDto()));
        } catch (UserAuthException $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    #[Route('/v1/user/profile', name: 'api_v1_user_profile', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        summary: 'Get current user profile',
        security: [['Bearer' => []]],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Current user profile',
                content: new OA\JsonContent(ref: '#/components/schemas/UserProfile'),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Authentication required'),
        ],
    )]
    public function profile(#[CurrentUser] UserInterface $user): JsonResponse
    {
        $profile = $this->userService->getProfileByEmail($user->getUserIdentifier());

        if (null === $profile) {
            return $this->json([], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($profile->toArray());
    }

    #[Route('/v1/user/refresh-token', name: 'api_v1_user_refresh_token', methods: ['POST'])]
    #[OA\Post(
        summary: 'Refresh access token using refresh token cookie',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Access token refreshed successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse'),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Refresh token is missing or invalid'),
        ],
    )]
    public function refreshToken(Request $request): JsonResponse
    {
        return $this->toJsonResponse(
            $this->authService->refresh(new RefreshTokenData($request->cookies->get($this->refreshCookieName))),
        );
    }

    #[Route('/v1/user/logout', name: 'api_v1_user_logout', methods: ['POST'])]
    #[OA\Post(
        summary: 'Log out current user',
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'User logged out successfully'),
        ],
    )]
    public function logout(Request $request): JsonResponse
    {
        return $this->toJsonResponse(
            $this->authService->logout(new RefreshTokenData($request->cookies->get($this->refreshCookieName))),
        );
    }

    private function toJsonResponse(\App\Dto\Auth\AuthResult $result): JsonResponse
    {
        if (Response::HTTP_NO_CONTENT === $result->statusCode) {
            $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } else {
            $response = $this->json($result->payload ?? [], $result->statusCode);
        }

        if (null !== $result->cookie) {
            $response->headers->setCookie($result->cookie);
        }

        return $response;
    }

    private function errorResponse(string $error, string $field = 'email'): JsonResponse
    {
        return $this->json([
            'message' => $error,
            'errors' => [$field => [$error]]
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
