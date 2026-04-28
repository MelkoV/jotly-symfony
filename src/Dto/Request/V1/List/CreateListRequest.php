<?php

declare(strict_types=1);

namespace App\Dto\Request\V1\List;

use App\Dto\List\CreateListData;
use App\Enum\ListType;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateListRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [self::class, 'listTypes'])]
        public string $type,
        #[Assert\NotNull]
        public bool $is_template,
        #[Assert\Length(max: 250)]
        public ?string $description = null,
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

    public function toDto(): CreateListData
    {
        return new CreateListData(
            trim($this->name),
            ListType::from($this->type),
            $this->is_template,
            null === $this->description ? null : trim($this->description),
        );
    }
}
