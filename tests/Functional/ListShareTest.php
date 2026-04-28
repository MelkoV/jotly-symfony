<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ListShareTest extends WebTestCase
{
    public function testDeleteTypesReturnsDeleteForOwner(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('Owner User');
        $list = $this->createList($client, $accessToken, 'Owner list');

        $client->request('GET', '/api/v1/lists/delete-types/'.$list['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame([
            'left' => true,
            'delete' => true,
        ], $this->decodeJson($client->getResponse()->getContent()));
    }

    public function testShareSettingsCanBeReadAndUpdated(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('Share Owner');
        $list = $this->createList($client, $accessToken, 'Shared list');

        $client->request('GET', '/api/v1/lists/share/'.$list['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $this->decodeJson($client->getResponse()->getContent());
        self::assertFalse($payload['is_share_link'] ?? true);
        self::assertFalse($payload['can_edit'] ?? true);
        self::assertIsString($payload['short_url'] ?? null);

        $client->jsonRequest('PUT', '/api/v1/lists/share/'.$list['id'], [
            'is_share_link' => true,
            'can_edit' => true,
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $updated = $this->decodeJson($client->getResponse()->getContent());
        self::assertTrue($updated['is_share_link'] ?? false);
        self::assertTrue($updated['can_edit'] ?? false);
    }

    public function testJoinByLinkAndLeftFlowWorks(): void
    {
        [$ownerClient, $ownerToken] = $this->signUpAndSignIn('Join Owner');
        $list = $this->createList($ownerClient, $ownerToken, 'Joinable list');

        $ownerClient->jsonRequest('PUT', '/api/v1/lists/share/'.$list['id'], [
            'is_share_link' => true,
            'can_edit' => true,
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$ownerToken,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        [$memberClient, $memberToken] = $this->signUpAndSignIn('Join Member');

        $memberClient->request('POST', '/api/v1/lists/join/'.$list['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$memberToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $joined = $this->decodeJson($memberClient->getResponse()->getContent());
        self::assertSame($list['id'], $joined['id'] ?? null);
        self::assertTrue($joined['can_edit'] ?? false);

        $memberClient->request('GET', '/api/v1/lists/delete-types/'.$list['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$memberToken,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame([
            'left' => true,
            'delete' => false,
        ], $this->decodeJson($memberClient->getResponse()->getContent()));

        $memberClient->request('DELETE', '/api/v1/lists/left/'.$list['id'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$memberToken,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame(['success' => true], $this->decodeJson($memberClient->getResponse()->getContent()));
    }

    public function testPublicInfoReturnsListDataByShortUrl(): void
    {
        [$client, $accessToken] = $this->signUpAndSignIn('Public Owner');
        $list = $this->createList($client, $accessToken, 'Public info list');

        $client->jsonRequest('PUT', '/api/v1/lists/share/'.$list['id'], [
            'is_share_link' => true,
            'can_edit' => false,
        ], server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $share = $this->decodeJson($client->getResponse()->getContent());

        self::ensureKernelShutdown();
        $publicClient = static::createClient();
        $publicClient->request('GET', '/api/v1/lists/info/'.$share['short_url']);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $payload = $this->decodeJson($publicClient->getResponse()->getContent());
        self::assertSame($list['id'], $payload['id'] ?? null);
        self::assertSame('Public info list', $payload['name'] ?? null);
        self::assertSame('Public Owner', $payload['owner_name'] ?? null);
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
        self::ensureKernelShutdown();
        $client = static::createClient();
        $email = sprintf('phpunit+%s@example.com', bin2hex(random_bytes(6)));

        $client->jsonRequest('POST', '/api/v1/user/sign-up', [
            'email' => $email,
            'password' => 'secret123',
            'repeat_password' => 'secret123',
            'name' => $name,
            'device' => 'web',
            'device_id' => 'phpunit-list-share',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->jsonRequest('POST', '/api/v1/user/sign-in', [
            'email' => $email,
            'password' => 'secret123',
            'device' => 'web',
            'device_id' => 'phpunit-list-share',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJson($client->getResponse()->getContent());

        return [$client, (string) ($payload['token'] ?? '')];
    }
}
