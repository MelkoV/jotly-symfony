<?php

declare(strict_types=1);

namespace App\Dto\List;

final readonly class PaginatedListsData
{
    /**
     * @param list<ListData> $data
     */
    public function __construct(
        public array $data,
        public int $currentPage,
        public int $perPage,
        public int $total,
        public int $lastPage,
        public ?int $from,
        public ?int $to,
    ) {
    }

    /**
     * @param array<string, scalar|null> $query
     *
     * @return array<string, mixed>
     */
    public function toArray(string $path, array $query = []): array
    {
        $firstPageUrl = $this->buildPageUrl($path, $query, 1);
        $lastPageUrl = $this->buildPageUrl($path, $query, $this->lastPage);
        $prevPage = $this->currentPage > 1 ? $this->currentPage - 1 : null;
        $nextPage = $this->currentPage < $this->lastPage ? $this->currentPage + 1 : null;

        return [
            'current_page' => $this->currentPage,
            'data' => array_map(
                static fn (ListData $item): array => $item->toArray(),
                $this->data,
            ),
            'first_page_url' => $firstPageUrl,
            'from' => $this->from,
            'last_page' => $this->lastPage,
            'last_page_url' => $lastPageUrl,
            'links' => $this->buildLinks($path, $query),
            'next_page_url' => null !== $nextPage ? $this->buildPageUrl($path, $query, $nextPage) : null,
            'path' => $path,
            'per_page' => $this->perPage,
            'prev_page_url' => null !== $prevPage ? $this->buildPageUrl($path, $query, $prevPage) : null,
            'to' => $this->to,
            'total' => $this->total,
        ];
    }

    /**
     * @param array<string, scalar|null> $query
     *
     * @return list<array{url: ?string, label: string, page: ?int, active: bool}>
     */
    private function buildLinks(string $path, array $query): array
    {
        $links = [[
            'url' => $this->currentPage > 1 ? $this->buildPageUrl($path, $query, $this->currentPage - 1) : null,
            'label' => '&laquo; Назад',
            'page' => $this->currentPage > 1 ? $this->currentPage - 1 : null,
            'active' => false,
        ]];

        for ($page = 1; $page <= $this->lastPage; ++$page) {
            $links[] = [
                'url' => $this->buildPageUrl($path, $query, $page),
                'label' => (string) $page,
                'page' => $page,
                'active' => $page === $this->currentPage,
            ];
        }

        $links[] = [
            'url' => $this->currentPage < $this->lastPage ? $this->buildPageUrl($path, $query, $this->currentPage + 1) : null,
            'label' => 'Вперёд &raquo;',
            'page' => $this->currentPage < $this->lastPage ? $this->currentPage + 1 : null,
            'active' => false,
        ];

        return $links;
    }

    /**
     * @param array<string, scalar|null> $query
     */
    private function buildPageUrl(string $path, array $query, int $page): string
    {
        $query['page'] = $page;

        return sprintf('%s?%s', $path, http_build_query(array_filter(
            $query,
            static fn (mixed $value): bool => null !== $value && '' !== $value,
        )));
    }
}
