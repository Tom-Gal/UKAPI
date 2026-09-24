<?php

namespace App\Domain\Companies;

use InvalidArgumentException;

final readonly class CompanySearchResults
{
    /** @param list<CompanySummary> $companies */
    public function __construct(
        public array $companies,
        public int $total,
        public bool $cached = false,
        public bool $stale = false,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromCompaniesHouse(array $payload): self
    {
        $items = $payload['items'] ?? [];

        if (! is_array($items)) {
            throw new InvalidArgumentException('Companies House search response contains invalid items.');
        }

        $companies = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Companies House search response contains an invalid item.');
            }

            $companies[] = CompanySummary::fromCompaniesHouse($item);
        }

        $total = $payload['total_results'] ?? count($companies);
        if (! is_int($total) && ! is_numeric($total)) {
            throw new InvalidArgumentException('Companies House search response contains an invalid total.');
        }

        return new self($companies, (int) $total);
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload, bool $cached, bool $stale): self
    {
        $items = $payload['items'] ?? [];
        if (! is_array($items)) {
            throw new InvalidArgumentException('Cached company search response contains invalid items.');
        }

        $companies = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Cached company search response contains an invalid item.');
            }

            $companies[] = CompanySummary::fromArray($item);
        }

        $total = $payload['total'] ?? null;
        if (! is_int($total) && ! is_numeric($total)) {
            throw new InvalidArgumentException('Cached company search response contains an invalid total.');
        }

        return new self($companies, (int) $total, $cached, $stale);
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    public function toCacheArray(): array
    {
        return [
            'items' => array_map(static fn (CompanySummary $company): array => $company->toArray(), $this->companies),
            'total' => $this->total,
        ];
    }
}
