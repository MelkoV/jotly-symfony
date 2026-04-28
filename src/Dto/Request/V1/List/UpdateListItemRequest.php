<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\List;

use App\Dto\List\ListItemMutationData;
use App\Enum\ProductUnit;
use App\Enum\TodoPriority;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateListItemRequest
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $name,
        #[Assert\Positive]
        public int $version,
        #[Assert\Choice(callback: [self::class, 'priorities'])]
        public ?string $priority = null,
        #[Assert\Length(max: 250)]
        public ?string $description = null,
        #[Assert\Choice(callback: [self::class, 'units'])]
        public ?string $unit = null,
        #[Assert\Date]
        public ?string $deadline = null,
        #[Assert\Regex(pattern: '/^\d{1,10}(\.\d{1,3})?$/')]
        public ?string $price = null,
        #[Assert\Regex(pattern: '/^\d{1,10}(\.\d{1,3})?$/')]
        public ?string $cost = null,
        #[Assert\Regex(pattern: '/^\d{1,10}(\.\d{1,3})?$/')]
        public ?string $count = null,
        public array $attributes = [],
    ) {
    }

    /**
     * @return list<string>
     */
    public static function priorities(): array
    {
        return array_map(static fn (TodoPriority $value): string => $value->value, TodoPriority::cases());
    }

    /**
     * @return list<string>
     */
    public static function units(): array
    {
        return array_map(static fn (ProductUnit $value): string => $value->value, ProductUnit::cases());
    }

    public function toDto(): ListItemMutationData
    {
        return new ListItemMutationData(
            trim($this->name),
            $this->version,
            $this->priority ?? $this->stringAttribute('priority'),
            $this->description ?? $this->stringAttribute('description'),
            $this->unit ?? $this->stringAttribute('unit'),
            $this->deadline ?? $this->stringAttribute('deadline'),
            $this->price ?? $this->stringAttribute('price'),
            $this->cost ?? $this->stringAttribute('cost'),
            $this->count ?? $this->stringAttribute('count'),
        );
    }

    private function stringAttribute(string $key): ?string
    {
        $value = $this->attributes[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
