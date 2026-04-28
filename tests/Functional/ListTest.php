<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ListTest extends WebTestCase
{
    public function testCreateListReturnsCreatedList(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('List Create User');

        $client->jsonRequest('POST', '/api/v1/lists', [
            'name' => 'Weekend groceries',
            'type' => 'shopping',
            'is_template' => false,
            'description' => 'Need fruit and coffee',
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $payload = $this->decodeJson($client->getResponse()->getContent());

        self::assertSame('Weekend groceries', $payload['name'] ?? null);
        self::assertSame('shopping', $payload['type'] ?? null);
        self::assertSame('List Create User', $payload['owner_name'] ?? null);
        self::assertSame('Need fruit and coffee', $payload['description'] ?? null);
        self::assertTrue($payload['can_edit'] ?? false);
        self::assertArrayHasKey('id', $payload);
    }

    public function testListIndexReturnsPaginatedLists(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('List Index User');
        $this->createList($client, $accessToken, 'Shopping list');

        $client->request('GET', '/api/v1/lists?is_owner=1&page=1&per_page=100', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $this->decodeJson($client->getResponse()->getContent());

        self::assertSame(1, $payload['current_page'] ?? null);
        self::assertSame(100, $payload['per_page'] ?? null);
        self::assertGreaterThanOrEqual(1, $payload['total'] ?? 0);
        self::assertIsArray($payload['data'] ?? null);
        self::assertSame('Shopping list', $payload['data'][0]['name'] ?? null);
    }

    public function testShowListReturnsModelAndItems(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('List View User');
        $created = $this->createList($client, $accessToken, 'View list');

        $client->request('GET', '/api/v1/lists/'.$created['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $this->decodeJson($client->getResponse()->getContent());

        self::assertSame($created['id'], $payload['model']['id'] ?? null);
        self::assertSame('View list', $payload['model']['name'] ?? null);
        self::assertSame([], $payload['items'] ?? null);
    }

    public function testUpdateListReturnsUpdatedList(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('List Update User');
        $created = $this->createList($client, $accessToken, 'Before');

        $client->jsonRequest('PUT', '/api/v1/lists/'.$created['id'], [
            'name' => 'After',
            'description' => 'Updated description',
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $this->decodeJson($client->getResponse()->getContent());

        self::assertSame($created['id'], $payload['id'] ?? null);
        self::assertSame('After', $payload['name'] ?? null);
        self::assertSame('Updated description', $payload['description'] ?? null);
    }

    public function testDeleteListReturnsSuccessAndHidesList(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('List Delete User');
        $created = $this->createList($client, $accessToken, 'Delete me');

        $client->request('DELETE', '/api/v1/lists/'.$created['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame(['success' => true], $this->decodeJson($client->getResponse()->getContent()));

        $client->request('GET', '/api/v1/lists/'.$created['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $payload = $this->decodeJson($client->getResponse()->getContent());
        self::assertSame('У Вас нет доступа к этому спискуа', $payload['message'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function createList(KernelBrowser $client, string $accessToken, string $name): array
    {
        $client->jsonRequest('POST', '/api/v1/lists', [
            'name' => $name,
            'type' => 'shopping',
            'is_template' => false,
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        return $this->decodeJson($client->getResponse()->getContent());
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
     * @return array{0: KernelBrowser, 1: string}
     */
    private function signUpAndSignIn(string $name): array
    {
        $client = static::createClient();
        $email = sprintf('phpunit+%s@example.com', bin2hex(random_bytes(6)));

        $client->jsonRequest('POST', '/api/v1/user/sign-up', [
            'email' => $email,
            'password' => 'secret123',
            'repeat_password' => 'secret123',
            'name' => $name,
            'device' => 'web',
            'device_id' => 'phpunit-lists',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->jsonRequest('POST', '/api/v1/user/sign-in', [
            'email' => $email,
            'password' => 'secret123',
            'device' => 'web',
            'device_id' => 'phpunit-lists',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJson($client->getResponse()->getContent());

        return [$client, (string) ($payload['token'] ?? '')];
    }
}
