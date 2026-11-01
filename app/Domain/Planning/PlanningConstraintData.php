<?php

namespace App\Domain\Planning;

use InvalidArgumentException;

final readonly class PlanningConstraintData
{
    public const DATASETS = [
        'conservation-area',
        'listed-building',
        'green-belt',
        'article-4-direction-area',
        'tree-preservation-zone',
    ];

    /**
     * @param  array{latitude: ?float, longitude: ?float}  $location
     */
    public function __construct(
        public string $id,
        public int $entityId,
        public string $dataset,
        public ?string $name,
        public ?string $reference,
        public ?string $startDate,
        public ?string $endDate,
        public array $location,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromPlanningData(array $payload): self
    {
        $entityId = self::integer($payload['entity'] ?? null);
        $dataset = self::string($payload['dataset'] ?? null);

        if ($entityId === null || $entityId < 1 || $dataset === null || ! in_array($dataset, self::DATASETS, true)) {
            throw new InvalidArgumentException('Planning constraint response is missing a supported identity.');
        }

        return new self(
            id: $dataset.':'.$entityId,
            entityId: $entityId,
            dataset: $dataset,
            name: self::string($payload['name'] ?? null),
            reference: self::string($payload['reference'] ?? null),
            startDate: self::string($payload['start-date'] ?? null),
            endDate: self::string($payload['end-date'] ?? null),
            location: self::point($payload['point'] ?? null),
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $location = is_array($data['location'] ?? null) ? $data['location'] : [];
        $entityId = self::integer($data['entity_id'] ?? null);
        $dataset = self::string($data['dataset'] ?? null);
        $id = self::string($data['id'] ?? null);

        if ($entityId === null || $entityId < 1 || $dataset === null || $id === null || $id !== $dataset.':'.$entityId || ! in_array($dataset, self::DATASETS, true)) {
            throw new InvalidArgumentException('Cached planning constraint data is incomplete.');
        }

        return new self(
            id: $id,
            entityId: $entityId,
            dataset: $dataset,
            name: self::string($data['name'] ?? null),
            reference: self::string($data['reference'] ?? null),
            startDate: self::string($data['start_date'] ?? null),
            endDate: self::string($data['end_date'] ?? null),
            location: [
                'latitude' => self::number($location['latitude'] ?? null),
                'longitude' => self::number($location['longitude'] ?? null),
            ],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'entity_id' => $this->entityId,
            'dataset' => $this->dataset,
            'name' => $this->name,
            'reference' => $this->reference,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'location' => $this->location,
        ];
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function integer(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value))
            ? (int) $value
            : null;
    }

    private static function number(mixed $value): ?float
    {
        return is_numeric($value) && is_finite((float) $value) ? (float) $value : null;
    }

    /** @return array{latitude: ?float, longitude: ?float} */
    private static function point(mixed $value): array
    {
        if (! is_string($value) || preg_match('/^POINT\s*\(\s*(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)\s*\)$/i', $value, $matches) !== 1) {
            return ['latitude' => null, 'longitude' => null];
        }

        $longitude = (float) $matches[1];
        $latitude = (float) $matches[2];

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return ['latitude' => null, 'longitude' => null];
        }

        return ['latitude' => $latitude, 'longitude' => $longitude];
    }
}
