<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ListItemTest extends WebTestCase
{
    public function testCreateListItemReturnsCreatedItem(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('List Item Create User');
        $list = $this->createList($client, $accessToken, 'Item list');

        $client->jsonRequest('POST', '/api/v1/list-items', [
            'list_id' => $list['id'],
            'name' => 'Milk',
            'priority' => 'high',
            'count' => '2.500',
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $payload = $this->decodeJson($client->getResponse()->getContent());

        self::assertSame('Milk', $payload['name'] ?? null);
        self::assertSame($list['id'], $payload['list_id'] ?? null);
        self::assertSame(1, $payload['version'] ?? null);
        self::assertFalse($payload['is_completed'] ?? true);
        self::assertSame('high', $payload['attributes']['priority'] ?? null);
        self::assertSame('2.500', $payload['attributes']['count'] ?? null);
    }

    public function testUpdateListItemReturnsUpdatedItem(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('List Item Update User');
        $list = $this->createList($client, $accessToken, 'Update item list');
        $item = $this->createListItem($client, $accessToken, $list['id'], 'Bread');

        $client->jsonRequest('PUT', '/api/v1/list-items/'.$item['id'], [
            'name' => 'Wholegrain bread',
            'version' => 1,
            'description' => 'Two packs',
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $this->decodeJson($client->getResponse()->getContent());

        self::assertSame('Wholegrain bread', $payload['name'] ?? null);
        self::assertSame(2, $payload['version'] ?? null);
        self::assertSame('Two packs', $payload['description'] ?? null);
    }

    public function testCompleteListItemReturnsCompletedItem(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('List Item Complete User');
        $list = $this->createList($client, $accessToken, 'Complete item list');
        $item = $this->createListItem($client, $accessToken, $list['id'], 'Eggs');

        $client->jsonRequest('PUT', '/api/v1/list-items/complete/'.$item['id'], [
            'name' => 'Eggs',
            'version' => 1,
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $this->decodeJson($client->getResponse()->getContent());

        self::assertTrue($payload['is_completed'] ?? false);
        self::assertSame(2, $payload['version'] ?? null);
        self::assertSame('List Item Complete User', $payload['completed_user_name'] ?? null);
    }

    public function testUncompleteListItemReturnsRestoredItem(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('List Item Restore User');
        $list = $this->createList($client, $accessToken, 'Restore item list');
        $item = $this->createListItem($client, $accessToken, $list['id'], 'Cheese');

        $client->jsonRequest('PUT', '/api/v1/list-items/complete/'.$item['id'], [
            'name' => 'Cheese',
            'version' => 1,
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $completed = $this->decodeJson($client->getResponse()->getContent());

        $client->jsonRequest('PUT', '/api/v1/list-items/uncomplete/'.$item['id'], [
            'name' => 'Cheese restored',
            'version' => $completed['version'],
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $this->decodeJson($client->getResponse()->getContent());

        self::assertFalse($payload['is_completed'] ?? true);
        self::assertSame(3, $payload['version'] ?? null);
        self::assertSame('Cheese restored', $payload['name'] ?? null);
        self::assertNull($payload['completed_user_name'] ?? null);
    }

    public function testDeleteListItemReturnsSuccess(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('List Item Delete User');
        $list = $this->createList($client, $accessToken, 'Delete item list');
        $item = $this->createListItem($client, $accessToken, $list['id'], 'Tea');

        $client->request('DELETE', '/api/v1/list-items/'.$item['id'].'?version=1', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame(['success' => true], $this->decodeJson($client->getResponse()->getContent()));

        $client->request('GET', '/api/v1/lists/'.$list['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJson($client->getResponse()->getContent());
        self::assertSame([], $payload['items'] ?? null);
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
    private function createListItem(KernelBrowser $client, string $accessToken, string $listId, string $name): array
    {
        $client->jsonRequest('POST', '/api/v1/list-items', [
            'list_id' => $listId,
            'name' => $name,
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
            'device_id' => 'phpunit-list-items',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->jsonRequest('POST', '/api/v1/user/sign-in', [
            'email' => $email,
            'password' => 'secret123',
            'device' => 'web',
            'device_id' => 'phpunit-list-items',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJson($client->getResponse()->getContent());

        return [$client, (string) ($payload['token'] ?? '')];
    }
}
