<?php

declare(strict_types=1);

namespace App\Controller\v1;

use App\Dto\Auth\RefreshTokenData;
use App\Dto\Request\V1\Auth\SignInRequest;
use App\Dto\Request\V1\Auth\SignUpRequest;
use App\Exception\UserAuthException;
use App\Service\AuthService;
use App\Service\UserService;
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
    public function signUp(#[MapRequestPayload] SignUpRequest $request): JsonResponse
    {
        try {
            return $this->toJsonResponse($this->authService->signUp($request->toDto()));
        } catch (UserAuthException $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    #[Route('/v1/user/sign-in', name: 'api_v1_user_sign_in', methods: ['POST'])]
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
    public function profile(#[CurrentUser] UserInterface $user): JsonResponse
    {
        $profile = $this->userService->getProfileByEmail($user->getUserIdentifier());

        if (null === $profile) {
            return $this->json([], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($profile->toArray());
    }

    #[Route('/v1/user/refresh-token', name: 'api_v1_user_refresh_token', methods: ['POST'])]
    public function refreshToken(Request $request): JsonResponse
    {
        return $this->toJsonResponse(
            $this->authService->refresh(new RefreshTokenData($request->cookies->get($this->refreshCookieName))),
        );
    }

    #[Route('/v1/user/logout', name: 'api_v1_user_logout', methods: ['POST'])]
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
