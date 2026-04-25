<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ProductUnit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product
{
    use TimestampableTrait;
    use SoftDeletableTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 20, enumType: ProductUnit::class)]
    private ProductUnit $unit;

    #[ORM\ManyToOne(targetEntity: ProductCategory::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false)]
    private ProductCategory $category;

    /** @var Collection<int, ListItem> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ListItem::class)]
    private Collection $listItems;

    public function __construct()
    {
        $this->listItems = new ArrayCollection();
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

    public function getUnit(): ProductUnit
    {
        return $this->unit;
    }

    public function setUnit(ProductUnit $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getCategory(): ProductCategory
    {
        return $this->category;
    }

    public function setCategory(ProductCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    /** @return Collection<int, ListItem> */
    public function getListItems(): Collection
    {
        return $this->listItems;
    }
}
