<?php

namespace App\Domain\Flood;

use InvalidArgumentException;

final readonly class FloodStation
{
    /**
     * @param  list<array{id: ?string, parameter: ?string, parameter_name: ?string, qualifier: ?string, unit: ?string, period_seconds: ?int}>  $measures
     */
    public function __construct(
        public string $id,
        public ?string $name,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $riverName,
        public ?string $town,
        public ?string $catchmentName,
        public ?string $status,
        public array $measures,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromEnvironmentAgency(array $payload): self
    {
        $id = self::string($payload['stationReference'] ?? null) ?? self::string($payload['notation'] ?? null);

        if ($id === null) {
            throw new InvalidArgumentException('Environment Agency station has no identifier.');
        }

        $measures = [];
        $sourceMeasures = is_array($payload['measures'] ?? null) ? $payload['measures'] : [];

        foreach ($sourceMeasures as $measure) {
            if (! is_array($measure)) {
                continue;
            }

            $measures[] = self::measure($measure);
        }

        return new self(
            id: $id,
            name: self::string($payload['label'] ?? null),
            latitude: self::number($payload['lat'] ?? null),
            longitude: self::number($payload['long'] ?? null),
            riverName: self::string($payload['riverName'] ?? null),
            town: self::string($payload['town'] ?? null),
            catchmentName: self::string($payload['catchmentName'] ?? null),
            status: self::lastPathSegment($payload['status'] ?? null),
            measures: $measures,
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = self::string($data['id'] ?? null);

        if ($id === null) {
            throw new InvalidArgumentException('Cached flood station has no identifier.');
        }

        $measures = [];
        $sourceMeasures = is_array($data['measures'] ?? null) ? $data['measures'] : [];

        foreach ($sourceMeasures as $measure) {
            if (! is_array($measure)) {
                throw new InvalidArgumentException('Cached flood station measure is invalid.');
            }

            $measures[] = self::measure($measure);
        }

        return new self(
            id: $id,
            name: self::string($data['name'] ?? null),
            latitude: self::number($data['latitude'] ?? null),
            longitude: self::number($data['longitude'] ?? null),
            riverName: self::string($data['river_name'] ?? null),
            town: self::string($data['town'] ?? null),
            catchmentName: self::string($data['catchment_name'] ?? null),
            status: self::string($data['status'] ?? null),
            measures: $measures,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'river_name' => $this->riverName,
            'town' => $this->town,
            'catchment_name' => $this->catchmentName,
            'status' => $this->status,
            'measures' => $this->measures,
        ];
    }

    /**
     * @param  array<string, mixed>  $measure
     * @return array{id: ?string, parameter: ?string, parameter_name: ?string, qualifier: ?string, unit: ?string, period_seconds: ?int}
     */
    private static function measure(array $measure): array
    {
        return [
            'id' => self::string($measure['@id'] ?? null) ?? self::string($measure['id'] ?? null),
            'parameter' => self::string($measure['parameter'] ?? null),
            'parameter_name' => self::string($measure['parameterName'] ?? null) ?? self::string($measure['parameter_name'] ?? null),
            'qualifier' => self::string($measure['qualifier'] ?? null),
            'unit' => self::string($measure['unitName'] ?? null) ?? self::string($measure['unit'] ?? null),
            'period_seconds' => self::integer($measure['period'] ?? null) ?? self::integer($measure['period_seconds'] ?? null),
        ];
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function lastPathSegment(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $segment = basename(parse_url($value, PHP_URL_PATH) ?: '');

        return $segment !== '' ? $segment : null;
    }
}
