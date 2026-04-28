<?php

declare(strict_types=1);

namespace App\Repository\Doctrine;

use App\Dto\Auth\AuthUser;
use App\Dto\Auth\CreateUserData;
use App\Dto\User\UserData;
use App\Entity\Account;
use App\Entity\User;
use App\Enum\UserDevice;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<User>
 */
final class DoctrineUserRepository extends ServiceEntityRepository implements UserRepository, PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function existsByEmail(string $email): bool
    {
        return null !== $this->findOneBy(['email' => $this->normalizeEmail($email)]);
    }

    public function createUser(CreateUserData $data): AuthUser
    {
        $user = new User();
        $user
            ->setId($data->id)
            ->setName($data->name)
            ->setEmail($this->normalizeEmail($data->email))
            ->setPassword($data->passwordHash)
            ->setStatus($data->status)
            ->setCreatedAt($data->createdAt)
            ->setUpdatedAt($data->updatedAt);

        $account = $this->createAccount(
            $data->id,
            $user,
            $data->device,
            $data->deviceId,
            $data->lastLoginAt,
            $data->createdAt,
            $data->updatedAt,
        );

        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        $entityManager->persist($account);
        $entityManager->flush();

        return $this->mapAuthUser($user);
    }

    public function findAuthUserByEmail(string $email): ?AuthUser
    {
        $user = $this->findOneBy(['email' => $this->normalizeEmail($email)]);

        return $user instanceof User ? $this->mapAuthUser($user) : null;
    }

    public function findProfileByEmail(string $email): ?UserData
    {
        $user = $this->findOneBy(['email' => $this->normalizeEmail($email)]);

        return $user instanceof User ? $this->mapUserData($user) : null;
    }

    public function updateNameByEmail(string $email, string $name): ?UserData
    {
        $user = $this->findOneBy(['email' => $this->normalizeEmail($email)]);

        if (!$user instanceof User) {
            return null;
        }

        $user
            ->setName(trim($name))
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $this->mapUserData($user);
    }

    public function touchAccount(string $userId, UserDevice $device, string $deviceId, \DateTimeImmutable $loggedAt): void
    {
        $user = $this->find($userId);

        if (!$user instanceof User) {
            return;
        }

        /** @var Account|null $account */
        $account = $this->getEntityManager()->getRepository(Account::class)->findOneBy([
            'user' => $user,
            'device' => $device,
            'deviceId' => trim($deviceId),
        ]);

        if (!$account instanceof Account) {
            $account = $this->createAccount(
                Uuid::v7()->toRfc4122(),
                $user,
                $device,
                $deviceId,
                $loggedAt,
                $loggedAt,
                $loggedAt,
            );
        }

        $account
            ->setLastLoginAt($loggedAt)
            ->setUpdatedAt($loggedAt);

        $this->getEntityManager()->persist($account);
        $this->getEntityManager()->flush();
    }

    public function upgradePasswordByEmail(string $email, string $newHashedPassword): void
    {
        $user = $this->findOneBy(['email' => $this->normalizeEmail($email)]);

        if (!$user instanceof User) {
            return;
        }

        $user->setPassword($newHashedPassword);
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if ($user instanceof AuthUser) {
            $this->upgradePasswordByEmail($user->getUserIdentifier(), $newHashedPassword);

            return;
        }

        if (!$user instanceof User) {
            return;
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    private function mapAuthUser(User $user): AuthUser
    {
        return new AuthUser($this->mapUserData($user), $user->getPassword());
    }

    private function mapUserData(User $user): UserData
    {
        return new UserData(
            $user->getId(),
            $user->getName(),
            $user->getEmail(),
            $user->getStatus(),
            $user->getAvatar(),
        );
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function createAccount(
        string $id,
        User $user,
        UserDevice $device,
        string $deviceId,
        \DateTimeImmutable $lastLoginAt,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): Account {
        $account = new Account();
        $account
            ->setId($id)
            ->setUser($user)
            ->setDevice($device)
            ->setDeviceId(trim($deviceId))
            ->setLastLoginAt($lastLoginAt)
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($updatedAt);

        return $account;
    }
}
