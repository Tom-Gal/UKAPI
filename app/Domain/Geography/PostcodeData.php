<?php

namespace App\Domain\Geography;

use InvalidArgumentException;

final readonly class PostcodeData
{
    /**
     * @param  array{name: ?string, code: ?string}  $localAuthority
     * @param  array{name: ?string, code: ?string}  $parliamentaryConstituency
     * @param  array{code: ?string}  $statisticalGeography
     */
    public function __construct(
        public string $postcode,
        public string $outcode,
        public string $incode,
        public string $country,
        public ?string $region,
        public ?float $latitude,
        public ?float $longitude,
        public array $localAuthority,
        public array $parliamentaryConstituency,
        public array $statisticalGeography,
        public ?float $distanceMetres = null,
    ) {}

    /**
     * @param  array<string, mixed>  $result
     */
    public static function fromPostcodesIo(array $result): self
    {
        $postcode = $result['postcode'] ?? null;

        if (! is_string($postcode) || $postcode === '') {
            throw new InvalidArgumentException('Postcodes.io response does not contain a postcode.');
        }

        $normalised = UkPostcode::from($postcode);
        $codes = is_array($result['codes'] ?? null) ? $result['codes'] : [];

        return new self(
            postcode: $normalised->display(),
            outcode: self::string($result['outcode'] ?? null) ?? substr($normalised->compact(), 0, -3),
            incode: self::string($result['incode'] ?? null) ?? substr($normalised->compact(), -3),
            country: self::string($result['country'] ?? null) ?? 'United Kingdom',
            region: self::string($result['region'] ?? null),
            latitude: self::number($result['latitude'] ?? null),
            longitude: self::number($result['longitude'] ?? null),
            localAuthority: [
                'name' => self::string($result['admin_district'] ?? null),
                'code' => self::string($codes['admin_district'] ?? null),
            ],
            parliamentaryConstituency: [
                'name' => self::string($result['parliamentary_constituency'] ?? null),
                'code' => self::string($codes['parliamentary_constituency'] ?? null),
            ],
            statisticalGeography: [
                'code' => self::string($codes['nuts'] ?? null),
            ],
            distanceMetres: self::number($result['distance'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $localAuthority = is_array($data['local_authority'] ?? null) ? $data['local_authority'] : [];
        $constituency = is_array($data['parliamentary_constituency'] ?? null) ? $data['parliamentary_constituency'] : [];
        $statisticalGeography = is_array($data['statistical_geography'] ?? null) ? $data['statistical_geography'] : [];

        return new self(
            postcode: self::requiredString($data, 'postcode'),
            outcode: self::requiredString($data, 'outcode'),
            incode: self::requiredString($data, 'incode'),
            country: self::requiredString($data, 'country'),
            region: self::string($data['region'] ?? null),
            latitude: self::number($data['latitude'] ?? null),
            longitude: self::number($data['longitude'] ?? null),
            localAuthority: [
                'name' => self::string($localAuthority['name'] ?? null),
                'code' => self::string($localAuthority['code'] ?? null),
            ],
            parliamentaryConstituency: [
                'name' => self::string($constituency['name'] ?? null),
                'code' => self::string($constituency['code'] ?? null),
            ],
            statisticalGeography: [
                'code' => self::string($statisticalGeography['code'] ?? null),
            ],
            distanceMetres: self::number($data['distance_metres'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'postcode' => $this->postcode,
            'outcode' => $this->outcode,
            'incode' => $this->incode,
            'country' => $this->country,
            'region' => $this->region,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'local_authority' => $this->localAuthority,
            'parliamentary_constituency' => $this->parliamentaryConstituency,
            'statistical_geography' => $this->statisticalGeography,
        ];

        if ($this->distanceMetres !== null) {
            $data['distance_metres'] = $this->distanceMetres;
        }

        return $data;
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /** @param array<string, mixed> $data */
    private static function requiredString(array $data, string $key): string
    {
        $value = self::string($data[$key] ?? null);

        if ($value === null) {
            throw new InvalidArgumentException(sprintf('Cached postcode data is missing %s.', $key));
        }

        return $value;
    }
}
