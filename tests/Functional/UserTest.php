<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class UserTest extends WebTestCase
{
    public function testSignUpReturnsUserTokenAndRefreshCookie(): void
    {
        $client = static::createClient();
        $email = $this->uniqueEmail();

        $client->jsonRequest('POST', '/api/v1/user/sign-up', [
            'email' => $email,
            'password' => 'secret123',
            'repeat_password' => 'secret123',
            'name' => 'Functional Test',
            'device' => 'web',
            'device_id' => 'phpunit-sign-up',
        ]);

        $response = $client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJson($response->getContent());
        self::assertSame($email, $payload['user']['email'] ?? null);
        self::assertSame('Functional Test', $payload['user']['name'] ?? null);
        self::assertIsString($payload['token'] ?? null);
        self::assertNotSame('', $payload['token'] ?? '');

        $cookie = $this->findCookie($response->headers->getCookies(), 'refresh_token');
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('/api', $cookie->getPath());
    }

    public function testSignInProfileRefreshAndLogoutFlow(): void
    {
        $client = static::createClient();
        $email = $this->uniqueEmail();

        $client->jsonRequest('POST', '/api/v1/user/sign-up', [
            'email' => $email,
            'password' => 'secret123',
            'repeat_password' => 'secret123',
            'name' => 'Flow Test',
            'device' => 'web',
            'device_id' => 'phpunit-flow',
        ]);
        self::assertResponseIsSuccessful();

        $client->jsonRequest('POST', '/api/v1/user/sign-in', [
            'email' => $email,
            'password' => 'secret123',
            'device' => 'web',
            'device_id' => 'phpunit-flow',
        ]);

        $signInResponse = $client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $signInPayload = $this->decodeJson($signInResponse->getContent());
        $accessToken = $signInPayload['token'] ?? null;

        self::assertIsString($accessToken);
        self::assertNotSame('', $accessToken ?? '');

        $client->request('GET', '/api/v1/user/profile', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        $profilePayload = $this->decodeJson($client->getResponse()->getContent());
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame($email, $profilePayload['email'] ?? null);
        self::assertSame('Flow Test', $profilePayload['name'] ?? null);
        self::assertArrayNotHasKey('token', $profilePayload);

        $client->request('POST', '/api/v1/user/refresh-token');

        $refreshResponse = $client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $refreshPayload = $this->decodeJson($refreshResponse->getContent());
        self::assertSame($email, $refreshPayload['user']['email'] ?? null);
        self::assertIsString($refreshPayload['token'] ?? null);

        $refreshCookie = $this->findCookie($refreshResponse->headers->getCookies(), 'refresh_token');
        self::assertNotNull($refreshCookie);
        self::assertTrue($refreshCookie->isHttpOnly());

        $client->request('POST', '/api/v1/user/logout');

        $logoutResponse = $client->getResponse();
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        self::assertSame('', $logoutResponse->getContent());

        $logoutCookie = $this->findCookie($logoutResponse->headers->getCookies(), 'refresh_token');
        self::assertNotNull($logoutCookie);
        self::assertTrue($logoutCookie->isCleared());
    }

    public function testUpdateProfileReturnsUpdatedUser(): void
    {
        [$client, $email, $accessToken] = $this->signUpAndSignIn('Profile Before');

        $client->jsonRequest('PUT', '/api/v1/user/profile', [
            'name' => 'Profile After',
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $this->decodeJson($client->getResponse()->getContent());

        self::assertSame($email, $payload['email'] ?? null);
        self::assertSame('Profile After', $payload['name'] ?? null);
        self::assertSame('active', $payload['status'] ?? null);
    }

    public function testChangePasswordReturnsSuccessAndAllowsSignInWithNewPassword(): void
    {
        [$client, $email, $accessToken] = $this->signUpAndSignIn('Password User');

        $client->jsonRequest('POST', '/api/v1/user/change-password', [
            'old_password' => 'secret123',
            'password' => 'new-secret123',
            'repeat_password' => 'new-secret123',
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame(['success' => true], $this->decodeJson($client->getResponse()->getContent()));

        $client->jsonRequest('POST', '/api/v1/user/sign-in', [
            'email' => $email,
            'password' => 'secret123',
            'device' => 'web',
            'device_id' => 'phpunit-password',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $client->jsonRequest('POST', '/api/v1/user/sign-in', [
            'email' => $email,
            'password' => 'new-secret123',
            'device' => 'web',
            'device_id' => 'phpunit-password',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJson($client->getResponse()->getContent());
        self::assertSame($email, $payload['user']['email'] ?? null);
        self::assertIsString($payload['token'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $content): array
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $payload;
    }

    /**
     * @param Cookie[] $cookies
     */
    private function findCookie(array $cookies, string $name): ?Cookie
    {
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }

        return null;
    }

    private function uniqueEmail(): string
    {
        return sprintf('phpunit+%s@example.com', bin2hex(random_bytes(6)));
    }

    /**
     * @return array{0: KernelBrowser, 1: string, 2: string}
     */
    private function signUpAndSignIn(string $name): array
    {
        $client = static::createClient();
        $email = $this->uniqueEmail();

        $client->jsonRequest('POST', '/api/v1/user/sign-up', [
            'email' => $email,
            'password' => 'secret123',
            'repeat_password' => 'secret123',
            'name' => $name,
            'device' => 'web',
            'device_id' => 'phpunit-password',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->jsonRequest('POST', '/api/v1/user/sign-in', [
            'email' => $email,
            'password' => 'secret123',
            'device' => 'web',
            'device_id' => 'phpunit-password',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJson($client->getResponse()->getContent());

        return [$client, $email, (string) ($payload['token'] ?? '')];
    }
}
