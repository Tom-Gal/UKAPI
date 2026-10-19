<?php

namespace App\Http\Requests\Api\V1\Concerns;

trait InteractsWithPagination
{
    public const MAX_PAGE = 10_000;

    public const MAX_PER_PAGE = 100;

    /** @return array<string, array<int, string>> */
    protected function paginationRules(): array
    {
        $plan = request()->attributes->get('ukapi.entitlements');
        $maxPerPage = is_array($plan)
            ? min(self::MAX_PER_PAGE, max(1, (int) ($plan['max_page_size'] ?? self::MAX_PER_PAGE)))
            : self::MAX_PER_PAGE;

        return [
            'page' => ['sometimes', 'integer', 'between:1,'.self::MAX_PAGE],
            'per_page' => ['sometimes', 'integer', 'between:1,'.$maxPerPage],
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
