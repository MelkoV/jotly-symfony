<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UserStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
class User
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $email;

    #[ORM\Column(length: 20, enumType: UserStatus::class)]
    private UserStatus $status;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 255)]
    private string $password;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $rememberToken = null;

    /** @var Collection<int, Account> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Account::class)]
    private Collection $accounts;

    /** @var Collection<int, JotList> */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: JotList::class)]
    private Collection $ownedLists;

    /** @var Collection<int, ListUser> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ListUser::class)]
    private Collection $listMemberships;

    /** @var Collection<int, ListItem> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ListItem::class)]
    private Collection $createdListItems;

    /** @var Collection<int, ListItem> */
    #[ORM\OneToMany(mappedBy: 'completedUser', targetEntity: ListItem::class)]
    private Collection $completedListItems;

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
        $this->ownedLists = new ArrayCollection();
        $this->listMemberships = new ArrayCollection();
        $this->createdListItems = new ArrayCollection();
        $this->completedListItems = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeImmutable $emailVerifiedAt): static
    {
        $this->emailVerifiedAt = $emailVerifiedAt;

        return $this;
    }

    public function getRememberToken(): ?string
    {
        return $this->rememberToken;
    }

    public function setRememberToken(?string $rememberToken): static
    {
        $this->rememberToken = $rememberToken;

        return $this;
    }

    /** @return Collection<int, Account> */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }

    /** @return Collection<int, JotList> */
    public function getOwnedLists(): Collection
    {
        return $this->ownedLists;
    }

    /** @return Collection<int, ListUser> */
    public function getListMemberships(): Collection
    {
        return $this->listMemberships;
    }

    /** @return Collection<int, ListItem> */
    public function getCreatedListItems(): Collection
    {
        return $this->createdListItems;
    }

    /** @return Collection<int, ListItem> */
    public function getCompletedListItems(): Collection
    {
        return $this->completedListItems;
    }
}
