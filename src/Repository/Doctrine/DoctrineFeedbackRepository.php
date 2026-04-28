<?php

declare(strict_types=1);

namespace App\Repository\Doctrine;

use App\Dto\Feedback\CreateFeedbackData;
use App\Entity\Feedback;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineFeedbackRepository implements FeedbackRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function create(CreateFeedbackData $data): void
    {
        $feedback = new Feedback();
        $feedback
            ->setId(Uuid::v7()->toRfc4122())
            ->setName($data->name)
            ->setEmail($data->email)
            ->setMessage($data->message)
            ->setDate(new \DateTimeImmutable());

        $this->entityManager->persist($feedback);
        $this->entityManager->flush();
    }
}
