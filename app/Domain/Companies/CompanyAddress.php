<?php

namespace App\Domain\Companies;

final readonly class CompanyAddress
{
    public function __construct(
        public ?string $addressLine1,
        public ?string $addressLine2,
        public ?string $locality,
        public ?string $region,
        public ?string $postalCode,
        public ?string $country,
    ) {}

    /** @param array<string, mixed> $address */
    public static function fromCompaniesHouse(array $address): self
    {
        return new self(
            addressLine1: self::string($address['address_line_1'] ?? null),
            addressLine2: self::string($address['address_line_2'] ?? null),
            locality: self::string($address['locality'] ?? null),
            region: self::string($address['region'] ?? null),
            postalCode: self::string($address['postal_code'] ?? null),
            country: self::string($address['country'] ?? null),
        );
    }

    /** @param array<string, mixed> $address */
    public static function fromArray(array $address): self
    {
        return new self(
            addressLine1: self::string($address['address_line_1'] ?? null),
            addressLine2: self::string($address['address_line_2'] ?? null),
            locality: self::string($address['locality'] ?? null),
            region: self::string($address['region'] ?? null),
            postalCode: self::string($address['postal_code'] ?? null),
            country: self::string($address['country'] ?? null),
        );
    }

    /** @return array<string, ?string> */
    public function toArray(): array
    {
        return [
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'locality' => $this->locality,
            'region' => $this->region,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
        ];
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
