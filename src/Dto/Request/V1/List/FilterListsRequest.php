<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\List;

use App\Dto\List\ListFilterData;
use App\Enum\ListFilterTemplate;
use App\Enum\ListType;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FilterListsRequest
{
    public function __construct(
        #[Assert\Length(max: 100)]
        public ?string $text = null,
        #[Assert\Choice(callback: [self::class, 'listTypes'])]
        public ?string $type = null,
        #[Assert\Choice(callback: [self::class, 'templateTypes'])]
        public ?string $template = null,
        #[Assert\NotNull]
        public bool $is_owner = false,
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Range(min: 1, max: 100)]
        public int $per_page = 100,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function listTypes(): array
    {
        return array_map(
            static fn (ListType $type): string => $type->value,
            ListType::cases(),
        );
    }

    /**
     * @return list<string>
     */
    public static function templateTypes(): array
    {
        return array_map(
            static fn (ListFilterTemplate $type): string => $type->value,
            ListFilterTemplate::cases(),
        );
    }

    public function toDto(): ListFilterData
    {
        return new ListFilterData(
            $this->page,
            $this->per_page,
            $this->is_owner,
            null !== $this->type ? ListType::from($this->type) : null,
            null !== $this->template ? ListFilterTemplate::from($this->template) : null,
            null === $this->text ? null : trim($this->text),
        );
    }
}
