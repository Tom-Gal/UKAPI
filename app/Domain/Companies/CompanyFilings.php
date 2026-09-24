<?php

namespace App\Domain\Companies;

use InvalidArgumentException;

final readonly class CompanyFilings
{
    /** @param list<FilingData> $filings */
    public function __construct(
        public array $filings,
        public int $total,
        public bool $cached = false,
        public bool $stale = false,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromCompaniesHouse(array $payload): self
    {
        $items = $payload['items'] ?? [];
        if (! is_array($items)) {
            throw new InvalidArgumentException('Companies House filing history response contains invalid items.');
        }

        $filings = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Companies House filing history response contains an invalid item.');
            }

            $filings[] = FilingData::fromCompaniesHouse($item);
        }

        $total = $payload['total_count'] ?? $payload['total_results'] ?? count($filings);
        if (! is_int($total) && ! is_numeric($total)) {
            throw new InvalidArgumentException('Companies House filing history response contains an invalid total.');
        }

        return new self($filings, (int) $total);
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload, bool $cached, bool $stale): self
    {
        $items = $payload['items'] ?? [];
        if (! is_array($items)) {
            throw new InvalidArgumentException('Cached company filing history response contains invalid items.');
        }

        $filings = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Cached company filing history response contains an invalid item.');
            }

            $filings[] = FilingData::fromArray($item);
        }

        $total = $payload['total'] ?? null;
        if (! is_int($total) && ! is_numeric($total)) {
            throw new InvalidArgumentException('Cached company filing history response contains an invalid total.');
        }

        return new self($filings, (int) $total, $cached, $stale);
    }

    /** @return array{items: list<array{transaction_id: ?string, category: ?string, type: ?string, filed_on: string, description: ?string, pages: ?int}>, total: int} */
    public function toCacheArray(): array
    {
        return [
            'items' => array_map(static fn (FilingData $filing): array => $filing->toArray(), $this->filings),
            'total' => $this->total,
        ];
    }
}
