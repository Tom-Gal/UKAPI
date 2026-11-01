<?php

namespace App\Domain\Food;

use InvalidArgumentException;

final readonly class FoodEstablishmentCollection
{
    /** @param list<FoodEstablishmentData> $establishments */
    public function __construct(
        public array $establishments,
        public int $total,
        public ?string $sourceUpdatedAt = null,
        public bool $cached = false,
        public bool $stale = false,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, bool $cached, bool $stale): self
    {
        $items = $data['items'] ?? null;
        $total = $data['total'] ?? null;

        if (! is_array($items) || (! is_int($total) && ! is_numeric($total))) {
            throw new InvalidArgumentException('Cached food establishment collection is invalid.');
        }

        $establishments = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Cached food establishment item is invalid.');
            }

            $establishments[] = FoodEstablishmentData::fromArray($item);
        }

        $sourceUpdatedAt = $data['source_updated_at'] ?? null;

        if ($sourceUpdatedAt !== null && ! is_string($sourceUpdatedAt)) {
            throw new InvalidArgumentException('Cached food source timestamp is invalid.');
        }

        return new self($establishments, (int) $total, $sourceUpdatedAt, $cached, $stale);
    }

    /** @return array{items: list<array<string, mixed>>, total: int, source_updated_at: ?string} */
    public function toCacheArray(): array
    {
        return [
            'items' => array_map(static fn (FoodEstablishmentData $item): array => $item->toArray(), $this->establishments),
            'total' => $this->total,
            'source_updated_at' => $this->sourceUpdatedAt,
        ];
    }
}
