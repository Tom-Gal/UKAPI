<?php

namespace App\Domain\Geography;

use App\Support\Api\ApiException;

final readonly class Coordinates
{
    private function __construct(
        public float $latitude,
        public float $longitude,
    ) {}

    public static function from(string $latitude, string $longitude): self
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            throw ApiException::invalidCoordinates();
        }

        $latitudeValue = (float) $latitude;
        $longitudeValue = (float) $longitude;

        if (! is_finite($latitudeValue) || ! is_finite($longitudeValue)
            || $latitudeValue < -90 || $latitudeValue > 90
            || $longitudeValue < -180 || $longitudeValue > 180) {
            throw ApiException::invalidCoordinates();
        }

        return new self($latitudeValue, $longitudeValue);
    }

    public function cacheKey(): string
    {
        return $this->normalise($this->latitude).':'.$this->normalise($this->longitude);
    }

    private function normalise(float $coordinate): string
    {
        return rtrim(rtrim(number_format($coordinate, 6, '.', ''), '0'), '.');
    }
}
