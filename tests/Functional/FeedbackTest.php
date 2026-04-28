<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Feedback;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class FeedbackTest extends WebTestCase
{
    public function testFeedbackCreatesDatabaseRecordAndReturnsSuccess(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/v1/feedback', [
            'name' => 'Anton',
            'email' => 'anton@example.com',
            'message' => 'Please add dark mode to the mobile app.',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame(['success' => true], $this->decodeJson($client->getResponse()->getContent()));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $feedback = $entityManager->getRepository(Feedback::class)->findOneBy([
            'email' => 'anton@example.com',
        ]);

        self::assertInstanceOf(Feedback::class, $feedback);
        self::assertSame('Anton', $feedback->getName());
        self::assertSame('Please add dark mode to the mobile app.', $feedback->getMessage());
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
}
