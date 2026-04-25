<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'list_items')]
class ListItem
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $version = 1;

    #[ORM\Column(options: ['default' => false])]
    private bool $isCompleted = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'completedListItems')]
    #[ORM\JoinColumn(name: 'completed_user_id', referencedColumnName: 'id', nullable: true)]
    private ?User $completedUser = null;

    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    private array $data = [];

    #[ORM\ManyToOne(targetEntity: JotList::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'list_id', referencedColumnName: 'id', nullable: false)]
    private JotList $list;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'createdListItems')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'listItems')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: true)]
    private ?Product $product = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
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

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getCompletedUser(): ?User
    {
        return $this->completedUser;
    }

    public function setCompletedUser(?User $completedUser): static
    {
        $this->completedUser = $completedUser;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getList(): JotList
    {
        return $this->list;
    }

    public function setList(JotList $list): static
    {
        $this->list = $list;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }
}
