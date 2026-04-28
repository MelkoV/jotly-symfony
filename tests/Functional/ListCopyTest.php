<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ListCopyTest extends WebTestCase
{
    public function testCopyCreatesNewListWithCopiedItems(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('Copy Owner');
        $list = $this->createList($client, $accessToken, 'Original list', false);
        $item = $this->createListItem($client, $accessToken, $list['id'], 'Milk');

        $client->jsonRequest('PUT', '/api/v1/list-items/complete/'.$item['id'], [
            'name' => 'Milk',
            'version' => 1,
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->jsonRequest('POST', '/api/v1/lists/copy/'.$list['id'], [
            'name' => 'Copied list',
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $this->decodeJson($client->getResponse()->getContent());
        self::assertSame('Copied list', $payload['name'] ?? null);
        self::assertSame('Copy Owner', $payload['owner_name'] ?? null);

        $client->request('GET', '/api/v1/lists/'.$payload['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $view = $this->decodeJson($client->getResponse()->getContent());
        self::assertSame('Copied list', $view['model']['name'] ?? null);
        self::assertCount(1, $view['items'] ?? []);
        self::assertSame('Milk', $view['items'][0]['name'] ?? null);
        self::assertFalse($view['items'][0]['is_completed'] ?? true);
        self::assertSame(1, $view['items'][0]['version'] ?? null);
    }

    public function testCreateFromTemplateForcesNonTemplateList(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('Template Owner');
        $template = $this->createList($client, $accessToken, 'Template list', true);
        $this->createListItem($client, $accessToken, $template['id'], 'Template item');

        $client->jsonRequest('POST', '/api/v1/lists/create-from-template/'.$template['id'], [
            'name' => 'Workspace from template',
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $this->decodeJson($client->getResponse()->getContent());
        self::assertSame('Workspace from template', $payload['name'] ?? null);

        $client->request('GET', '/api/v1/lists/'.$payload['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $view = $this->decodeJson($client->getResponse()->getContent());
        self::assertFalse($view['model']['is_template'] ?? true);
        self::assertCount(1, $view['items'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function createList(KernelBrowser $client, string $accessToken, string $name, bool $isTemplate): array
    {
        $client->jsonRequest('POST', '/api/v1/lists', [
            'name' => $name,
            'type' => 'shopping',
            'is_template' => $isTemplate,
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
        self::ensureKernelShutdown();
        $client = static::createClient();
        $email = sprintf('phpunit+%s@example.com', bin2hex(random_bytes(6)));

        $client->jsonRequest('POST', '/api/v1/user/sign-up', [
            'email' => $email,
            'password' => 'secret123',
            'repeat_password' => 'secret123',
            'name' => $name,
            'device' => 'web',
            'device_id' => 'phpunit-list-copy',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->jsonRequest('POST', '/api/v1/user/sign-in', [
            'email' => $email,
            'password' => 'secret123',
            'device' => 'web',
            'device_id' => 'phpunit-list-copy',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJson($client->getResponse()->getContent());

        return [$client, (string) ($payload['token'] ?? '')];
    }
}
