<?php

declare(strict_types=1);

namespace App\Dto\List;

final readonly class ShareData
{
    public function __construct(
        public string $shortUrl,
        public bool $isShareLink,
        public bool $canEdit,
    ) {
    }

    /**
     * @return array{short_url: string, is_share_link: bool, can_edit: bool}
     */
    public function toArray(): array
    {
        return [
            'short_url' => $this->shortUrl,
            'is_share_link' => $this->isShareLink,
            'can_edit' => $this->canEdit,
        ];
    }
}
