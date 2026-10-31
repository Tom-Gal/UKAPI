<?php

namespace App\Domain\Geography;

use InvalidArgumentException;

final readonly class OutcodeData
{
    /**
     * @param  list<string>  $adminDistricts
     * @param  list<string>  $parishes
     * @param  list<string>  $adminCounties
     * @param  list<string>  $adminWards
     * @param  list<string>  $countries
     * @param  list<string>  $parliamentaryConstituencies
     */
    public function __construct(
        public string $outcode,
        public ?float $latitude,
        public ?float $longitude,
        public ?int $northings,
        public ?int $eastings,
        public array $adminDistricts,
        public array $parishes,
        public array $adminCounties,
        public array $adminWards,
        public array $countries,
        public array $parliamentaryConstituencies,
    ) {}

    /** @param array<string, mixed> $result */
    public static function fromPostcodesIo(array $result): self
    {
        $outcode = self::string($result['outcode'] ?? null);

        if ($outcode === null) {
            throw new InvalidArgumentException('Postcodes.io response does not contain an outcode.');
        }

        return new self(
            outcode: UkOutcode::from($outcode)->value(),
            latitude: self::number($result['latitude'] ?? null),
            longitude: self::number($result['longitude'] ?? null),
            northings: self::integer($result['northings'] ?? null),
            eastings: self::integer($result['eastings'] ?? null),
            adminDistricts: self::strings($result['admin_district'] ?? []),
            parishes: self::strings($result['parish'] ?? []),
            adminCounties: self::strings($result['admin_county'] ?? []),
            adminWards: self::strings($result['admin_ward'] ?? []),
            countries: self::strings($result['country'] ?? []),
            parliamentaryConstituencies: self::strings($result['parliamentary_constituency'] ?? []),
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $outcode = self::string($data['outcode'] ?? null);

        if ($outcode === null) {
            throw new InvalidArgumentException('Cached outcode data is missing outcode.');
        }

        return new self(
            outcode: UkOutcode::from($outcode)->value(),
            latitude: self::number($data['latitude'] ?? null),
            longitude: self::number($data['longitude'] ?? null),
            northings: self::integer($data['northings'] ?? null),
            eastings: self::integer($data['eastings'] ?? null),
            adminDistricts: self::strings($data['admin_districts'] ?? []),
            parishes: self::strings($data['parishes'] ?? []),
            adminCounties: self::strings($data['admin_counties'] ?? []),
            adminWards: self::strings($data['admin_wards'] ?? []),
            countries: self::strings($data['countries'] ?? []),
            parliamentaryConstituencies: self::strings($data['parliamentary_constituencies'] ?? []),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'outcode' => $this->outcode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'northings' => $this->northings,
            'eastings' => $this->eastings,
            'admin_districts' => $this->adminDistricts,
            'parishes' => $this->parishes,
            'admin_counties' => $this->adminCounties,
            'admin_wards' => $this->adminWards,
            'countries' => $this->countries,
            'parliamentary_constituencies' => $this->parliamentaryConstituencies,
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

    /** @return list<string> */
    private static function strings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => is_string($item) && $item !== ''));
    }
}
