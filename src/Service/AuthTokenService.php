<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Auth\AuthUser;
use App\Dto\Auth\IssuedTokens;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;

final readonly class AuthTokenService
{
    public function __construct(
        private JWTTokenManagerInterface $jwtTokenManager,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
        #[Autowire('%env(int:JWT_ACCESS_TTL)%')]
        private int $accessTokenTtl,
        #[Autowire('%env(int:JWT_REFRESH_TTL)%')]
        private int $refreshTokenTtl,
        #[Autowire('%env(JWT_REFRESH_COOKIE_NAME)%')]
        private string $refreshCookieName,
        #[Autowire('%env(bool:JWT_REFRESH_COOKIE_SECURE)%')]
        private bool $refreshCookieSecure,
        #[Autowire('%env(JWT_REFRESH_COOKIE_SAMESITE)%')]
        private string $refreshCookieSameSite,
        #[Autowire('%env(JWT_REFRESH_COOKIE_PATH)%')]
        private string $refreshCookiePath,
    ) {
    }

    /**
     */
    public function issueTokens(AuthUser $user, ?RefreshTokenInterface $previousRefreshToken = null): IssuedTokens
    {
        if (null !== $previousRefreshToken) {
            $this->refreshTokenManager->delete($previousRefreshToken);
        }

        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        $this->refreshTokenManager->save($refreshToken);

        return new IssuedTokens(
            $this->jwtTokenManager->create($user),
            'Bearer',
            $this->accessTokenTtl,
            $this->createRefreshCookie($refreshToken),
        );
    }

    public function createClearRefreshCookie(): Cookie
    {
        return $this->createCookie();
    }

    private function createRefreshCookie(RefreshTokenInterface $refreshToken): Cookie
    {
        $valid = $refreshToken->getValid();
        $expiresAt = $valid instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($valid) : new \DateTimeImmutable(sprintf('+%d seconds', $this->refreshTokenTtl));

        return $this->createCookie((string) $refreshToken, $expiresAt);
    }

    private function createCookie(string $refreshToken = '', \DateTimeInterface|int|string $expiresAt = 1): Cookie
    {
        return Cookie::create($this->refreshCookieName)
            ->withValue($refreshToken)
            ->withExpires($expiresAt)
            ->withPath($this->refreshCookiePath)
            ->withHttpOnly(true)
            ->withSecure($this->refreshCookieSecure)
            ->withSameSite($this->normalizeSameSite($this->refreshCookieSameSite));
    }

    private function normalizeSameSite(string $sameSite): ?string
    {
        return match (strtolower($sameSite)) {
            'none' => Cookie::SAMESITE_NONE,
            'strict' => Cookie::SAMESITE_STRICT,
            default => Cookie::SAMESITE_LAX,
        };
    }
}
