<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ListType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'lists')]
#[ORM\UniqueConstraint(name: 'uniq_lists_short_url', columns: ['short_url'])]
class JotList
{
    use TimestampableTrait;
    use SoftDeletableTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isTemplate = false;

    #[ORM\Column(length: 20, enumType: ListType::class)]
    private ListType $type;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $touchedAt;

    #[ORM\Column(name: 'short_url', length: 255)]
    private string $shortUrl;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $access = 1;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'ownedLists')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false)]
    private User $owner;

    /** @var Collection<int, ListUser> */
    #[ORM\OneToMany(targetEntity: ListUser::class, mappedBy: 'list')]
    private Collection $memberships;

    /** @var Collection<int, ListInvite> */
    #[ORM\OneToMany(targetEntity: ListInvite::class, mappedBy: 'list')]
    private Collection $invites;

    /** @var Collection<int, ListItem> */
    #[ORM\OneToMany(targetEntity: ListItem::class, mappedBy: 'list')]
    private Collection $items;

    public function __construct()
    {
        $this->memberships = new ArrayCollection();
        $this->invites = new ArrayCollection();
        $this->items = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isTemplate(): bool
    {
        return $this->isTemplate;
    }

    public function setIsTemplate(bool $isTemplate): static
    {
        $this->isTemplate = $isTemplate;

        return $this;
    }

    public function getType(): ListType
    {
        return $this->type;
    }

    public function setType(ListType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTouchedAt(): \DateTimeImmutable
    {
        return $this->touchedAt;
    }

    public function setTouchedAt(\DateTimeImmutable $touchedAt): static
    {
        $this->touchedAt = $touchedAt;

        return $this;
    }

    public function getShortUrl(): string
    {
        return $this->shortUrl;
    }

    public function setShortUrl(string $shortUrl): static
    {
        $this->shortUrl = $shortUrl;

        return $this;
    }

    public function getAccess(): int
    {
        return $this->access;
    }

    public function setAccess(int $access): static
    {
        $this->access = $access;

        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /** @return Collection<int, ListUser> */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    /** @return Collection<int, ListInvite> */
    public function getInvites(): Collection
    {
        return $this->invites;
    }

    /** @return Collection<int, ListItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }
}
