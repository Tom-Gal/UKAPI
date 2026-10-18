<?php

namespace App\Http\Requests\Api\V1\Concerns;

trait InteractsWithPagination
{
    public const MAX_PAGE = 10_000;

    public const MAX_PER_PAGE = 100;

    /** @return array<string, array<int, string>> */
    protected function paginationRules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'between:1,'.self::MAX_PAGE],
            'per_page' => ['sometimes', 'integer', 'between:1,'.self::MAX_PER_PAGE],
        ];
    }

    public function pageNumber(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 25);
    }
}
