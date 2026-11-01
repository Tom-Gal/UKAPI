<?php

namespace App\Domain\Planning;

use InvalidArgumentException;

final readonly class PlanningConstraintCollection
{
    /** @param list<PlanningConstraintData> $constraints */
    public function __construct(
        public array $constraints,
        public bool $cached = false,
        public bool $stale = false,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, bool $cached, bool $stale): self
    {
        $items = $data['items'] ?? null;

        if (! is_array($items) || ! array_is_list($items)) {
            throw new InvalidArgumentException('Cached planning constraint collection is invalid.');
        }

        $constraints = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Cached planning constraint item is invalid.');
            }

            $constraints[] = PlanningConstraintData::fromArray($item);
        }

        return new self($constraints, $cached, $stale);
    }

    /** @return array{items: list<array<string, mixed>>} */
    public function toCacheArray(): array
    {
        return [
            'items' => array_map(
                static fn (PlanningConstraintData $constraint): array => $constraint->toArray(),
                $this->constraints,
            ),
        ];
    }
}
