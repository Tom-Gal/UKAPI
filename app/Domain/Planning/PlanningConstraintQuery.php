<?php

namespace App\Domain\Planning;

use App\Domain\Geography\UkPostcode;

final readonly class PlanningConstraintQuery
{
    private function __construct(
        public ?UkPostcode $postcode,
        public float $latitude,
        public float $longitude,
        public int $limit,
    ) {}

    public static function fromPostcode(UkPostcode $postcode, int $limit): self
    {
        return new self($postcode, 0.0, 0.0, $limit);
    }

    public static function fromCoordinates(float $latitude, float $longitude, int $limit): self
    {
        return new self(null, $latitude, $longitude, $limit);
    }

    public function atCoordinates(float $latitude, float $longitude): self
    {
        return new self($this->postcode, $latitude, $longitude, $this->limit);
    }

    /** @return array<string, int|float|string|null> */
    public function cacheData(): array
    {
        return [
            'postcode' => $this->postcode?->compact(),
            'latitude' => round($this->latitude, 6),
            'longitude' => round($this->longitude, 6),
            'limit' => $this->limit,
        ];
    }
}
