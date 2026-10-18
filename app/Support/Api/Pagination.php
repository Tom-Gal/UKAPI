<?php

namespace App\Support\Api;

final readonly class Pagination
{
    private function __construct(
        private int $page,
        private int $perPage,
        private int $total,
    ) {}

    public static function from(int $page, int $perPage, int $total): self
    {
        return new self($page, $perPage, max(0, $total));
    }

    /** @return array{page: int, per_page: int, total: int, total_pages: int, next_page: ?int, previous_page: ?int} */
    public function toArray(): array
    {
        $totalPages = (int) ceil($this->total / $this->perPage);

        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'total_pages' => $totalPages,
            'next_page' => $this->page < $totalPages ? $this->page + 1 : null,
            'previous_page' => $this->page > 1 && $totalPages > 0 ? $this->page - 1 : null,
        ];
    }
}
