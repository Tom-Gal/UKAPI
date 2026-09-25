<?php

namespace App\Domain\Crime;

use InvalidArgumentException;

final readonly class CrimeData
{
    public function __construct(
        public string $category,
        public ?string $persistentId,
        public ?string $month,
        public ?string $locationType,
        public ?string $locationSubtype,
        public ?float $latitude,
        public ?float $longitude,
        public ?int $streetId,
        public ?string $streetName,
        public ?string $outcomeCategory,
        public ?string $outcomeMonth,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromPoliceUk(array $payload): self
    {
        $category = self::string($payload['category'] ?? null);

        if ($category === null) {
            throw new InvalidArgumentException('Police.uk crime response has no category.');
        }

        $location = is_array($payload['location'] ?? null) ? $payload['location'] : [];
        $street = is_array($location['street'] ?? null) ? $location['street'] : [];
        $outcome = is_array($payload['outcome_status'] ?? null) ? $payload['outcome_status'] : [];

        return new self(
            category: $category,
            persistentId: self::string($payload['persistent_id'] ?? null),
            month: self::yearMonth($payload['month'] ?? null),
            locationType: self::string($payload['location_type'] ?? null),
            locationSubtype: self::string($payload['location_subtype'] ?? null),
            latitude: self::number($location['latitude'] ?? null),
            longitude: self::number($location['longitude'] ?? null),
            streetId: self::integer($street['id'] ?? null),
            streetName: self::string($street['name'] ?? null),
            outcomeCategory: self::string($outcome['category'] ?? null),
            outcomeMonth: self::yearMonth($outcome['date'] ?? null),
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $category = self::string($data['category'] ?? null);

        if ($category === null) {
            throw new InvalidArgumentException('Cached crime data has no category.');
        }

        $location = is_array($data['location'] ?? null) ? $data['location'] : [];
        $street = is_array($location['street'] ?? null) ? $location['street'] : [];
        $outcome = is_array($data['outcome'] ?? null) ? $data['outcome'] : [];

        return new self(
            category: $category,
            persistentId: self::string($data['persistent_id'] ?? null),
            month: self::yearMonth($data['month'] ?? null),
            locationType: self::string($location['type'] ?? null),
            locationSubtype: self::string($location['subtype'] ?? null),
            latitude: self::number($location['latitude'] ?? null),
            longitude: self::number($location['longitude'] ?? null),
            streetId: self::integer($street['id'] ?? null),
            streetName: self::string($street['name'] ?? null),
            outcomeCategory: self::string($outcome['category'] ?? null),
            outcomeMonth: self::yearMonth($outcome['month'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'persistent_id' => $this->persistentId,
            'month' => $this->month,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'street' => [
                    'id' => $this->streetId,
                    'name' => $this->streetName,
                ],
                'type' => $this->locationType,
                'subtype' => $this->locationSubtype,
                'approximate' => true,
            ],
            'outcome' => [
                'category' => $this->outcomeCategory,
                'month' => $this->outcomeMonth,
            ],
        ];
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function yearMonth(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value) === 1 ? $value : null;
    }
}
