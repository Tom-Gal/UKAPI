<?php

namespace App\Domain\Food;

use App\Domain\Geography\UkPostcode;

final readonly class FoodEstablishmentSearch
{
    public function __construct(
        public ?string $name,
        public ?UkPostcode $postcode,
        public ?float $latitude,
        public ?float $longitude,
        public ?float $radiusMiles,
        public ?string $schemeType,
        public ?string $rating,
        public ?string $ratingOperator,
        public int $page,
        public int $perPage,
    ) {}

    public static function search(
        ?string $name,
        ?UkPostcode $postcode,
        ?string $schemeType,
        ?string $rating,
        ?string $ratingOperator,
        int $page,
        int $perPage,
    ): self {
        return new self(
            name: self::text($name),
            postcode: $postcode,
            latitude: null,
            longitude: null,
            radiusMiles: null,
            schemeType: self::text($schemeType),
            rating: self::text($rating),
            ratingOperator: self::text($ratingOperator),
            page: $page,
            perPage: $perPage,
        );
    }

    public static function nearby(
        UkPostcode $postcode,
        float $latitude,
        float $longitude,
        int $radiusKilometres,
        ?string $schemeType,
        ?string $rating,
        ?string $ratingOperator,
        int $page,
        int $perPage,
    ): self {
        return new self(
            name: null,
            postcode: $postcode,
            latitude: $latitude,
            longitude: $longitude,
            radiusMiles: $radiusKilometres / 1.609344,
            schemeType: self::text($schemeType),
            rating: self::text($rating),
            ratingOperator: self::text($ratingOperator),
            page: $page,
            perPage: $perPage,
        );
    }

    /** @return array<string, int|float|string|null> */
    public function cacheData(): array
    {
        return [
            'name' => $this->name,
            'postcode' => $this->postcode?->compact(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius_miles' => $this->radiusMiles,
            'scheme_type' => $this->schemeType,
            'rating' => $this->rating,
            'rating_operator' => $this->ratingOperator,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }

    private static function text(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalised = trim((string) preg_replace('/\s+/', ' ', $value));

        return $normalised === '' ? null : $normalised;
    }
}
