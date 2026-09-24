<?php

namespace App\Domain\Companies;

use InvalidArgumentException;

final readonly class CompanyOfficers
{
    /** @param list<OfficerData> $officers */
    public function __construct(
        public array $officers,
        public int $total,
        public bool $cached = false,
        public bool $stale = false,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromCompaniesHouse(array $payload): self
    {
        $items = $payload['items'] ?? [];
        if (! is_array($items)) {
            throw new InvalidArgumentException('Companies House officers response contains invalid items.');
        }

        $officers = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Companies House officers response contains an invalid item.');
            }

            $officers[] = OfficerData::fromCompaniesHouse($item);
        }

        $total = $payload['total_results'] ?? count($officers);
        if (! is_int($total) && ! is_numeric($total)) {
            throw new InvalidArgumentException('Companies House officers response contains an invalid total.');
        }

        return new self($officers, (int) $total);
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload, bool $cached, bool $stale): self
    {
        $items = $payload['items'] ?? [];
        if (! is_array($items)) {
            throw new InvalidArgumentException('Cached company officers response contains invalid items.');
        }

        $officers = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Cached company officers response contains an invalid item.');
            }

            $officers[] = OfficerData::fromArray($item);
        }

        $total = $payload['total'] ?? null;
        if (! is_int($total) && ! is_numeric($total)) {
            throw new InvalidArgumentException('Cached company officers response contains an invalid total.');
        }

        return new self($officers, (int) $total, $cached, $stale);
    }

    /** @return array{items: list<array<string, ?string>>, total: int} */
    public function toCacheArray(): array
    {
        return [
            'items' => array_map(static fn (OfficerData $officer): array => $officer->toArray(), $this->officers),
            'total' => $this->total,
        ];
    }
}
