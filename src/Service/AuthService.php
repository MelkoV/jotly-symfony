<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Auth\AuthResult;
use App\Dto\Auth\CreateUserData;
use App\Dto\Auth\PasswordHashSubject;
use App\Dto\Auth\RefreshTokenData;
use App\Dto\Auth\SignInData;
use App\Dto\Auth\SignUpData;
use App\Enum\UserStatus;
use App\Exception\UserException;
use App\Repository\UserRepository;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private AuthTokenService $authTokenService,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private TranslatorInterface $translator,
    ) {
    }

    public function signUp(SignUpData $data): AuthResult
    {
        if ($this->userRepository->existsByEmail($data->email)) {
            throw new UserException($this->translator->trans('security.user_already_exists'));
        }

        $now = new \DateTimeImmutable();
        $userId = Uuid::v7()->toRfc4122();
        $hashedPassword = $this->passwordHasher->hashPassword(
            new PasswordHashSubject($data->email),
            $data->password,
        );

        $authUser = $this->userRepository->createUser(new CreateUserData(
            $userId,
            $data->name,
            $data->email,
            $hashedPassword,
            UserStatus::Active,
            $data->device,
            $data->deviceId,
            $now,
            $now,
            $now,
        ));

        $tokens = $this->authTokenService->issueTokens($authUser);

        return new AuthResult([
            'user' => $authUser->user->toArray(),
            'token' => $tokens->accessToken,
        ], Response::HTTP_OK, $tokens->refreshCookie);
    }

    public function signIn(SignInData $data): AuthResult
    {
        $authUser = $this->userRepository->findAuthUserByEmail($data->email);

        if (null === $authUser || !$this->passwordHasher->isPasswordValid($authUser, $data->password)) {
            throw new UserException($this->translator->trans('security.invalid_credentials'));
        }

        if ($authUser->user->status !== UserStatus::Active) {
            throw new UserException($this->translator->trans('security.user_blocked'));
        }

        $this->userRepository->touchAccount($authUser->user->id, $data->device, $data->deviceId, new \DateTimeImmutable());
        $tokens = $this->authTokenService->issueTokens($authUser);

        return new AuthResult([
            'user' => $authUser->user->toArray(),
            'token' => $tokens->accessToken,
        ], Response::HTTP_OK, $tokens->refreshCookie);
    }

    public function refresh(RefreshTokenData $data): AuthResult
    {
        if (null === $data->token || '' === $data->token) {
            throw new UserException($this->translator->trans('security.refresh_token_is_missing'));
        }

        $refreshToken = $this->refreshTokenManager->get($data->token);

        if (null === $refreshToken || !$refreshToken->isValid()) {
            throw new UserException($this->translator->trans('security.refresh_token_is_invalid'));
        }

        $authUser = $this->userRepository->findAuthUserByEmail((string) $refreshToken->getUsername());

        if (null === $authUser || $authUser->user->status !== UserStatus::Active) {
            $this->refreshTokenManager->delete($refreshToken);
            throw new UserException($this->translator->trans('security.refresh_token_owner_error'));
        }

        $tokens = $this->authTokenService->issueTokens($authUser, $refreshToken);

        return new AuthResult([
            'user' => $authUser->user->toArray(),
            'token' => $tokens->accessToken,
        ], Response::HTTP_OK, $tokens->refreshCookie);
    }

    public function logout(RefreshTokenData $data): AuthResult
    {
        if (null !== $data->token && '' !== $data->token) {
            $refreshToken = $this->refreshTokenManager->get($data->token);

            if (null !== $refreshToken) {
                $this->refreshTokenManager->delete($refreshToken);
            }
        }

        return new AuthResult(
            null,
            Response::HTTP_NO_CONTENT,
            $this->authTokenService->createClearRefreshCookie(),
        );
    }
}
