<?php

namespace App\Domain\Companies;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class CompanySummary
{
    public function __construct(
        public string $companyNumber,
        public string $name,
        public ?string $status,
        public ?string $type,
        public ?string $incorporatedOn,
        public ?CompanyAddress $address,
    ) {}

    /** @param array<string, mixed> $company */
    public static function fromCompaniesHouse(array $company): self
    {
        $companyNumber = self::requiredString($company, 'company_number');
        $normalisedNumber = strtoupper($companyNumber);

        if (! CompanyNumber::isValid($normalisedNumber)) {
            throw new InvalidArgumentException('Companies House returned an invalid company number.');
        }

        return new self(
            companyNumber: $normalisedNumber,
            name: self::requiredString($company, 'title'),
            status: self::string($company['company_status'] ?? null),
            type: self::string($company['company_type'] ?? null),
            incorporatedOn: self::date($company['date_of_creation'] ?? null),
            address: is_array($company['address'] ?? null) ? CompanyAddress::fromCompaniesHouse($company['address']) : null,
        );
    }

    /** @param array<string, mixed> $company */
    public static function fromArray(array $company): self
    {
        return new self(
            companyNumber: self::requiredString($company, 'company_number'),
            name: self::requiredString($company, 'name'),
            status: self::string($company['status'] ?? null),
            type: self::string($company['type'] ?? null),
            incorporatedOn: self::date($company['incorporated_on'] ?? null),
            address: is_array($company['address'] ?? null) ? CompanyAddress::fromArray($company['address']) : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'company_number' => $this->companyNumber,
            'name' => $this->name,
            'status' => $this->status,
            'type' => $this->type,
            'incorporated_on' => $this->incorporatedOn,
            'address' => $this->address?->toArray(),
        ];
    }

    /** @param array<string, mixed> $data */
    private static function requiredString(array $data, string $key): string
    {
        $value = self::string($data[$key] ?? null);

        if ($value === null) {
            throw new InvalidArgumentException(sprintf('Companies House response is missing %s.', $key));
        }

        return $value;
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function date(mixed $value): ?string
    {
        $date = self::string($value);

        if ($date === null) {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Companies House returned an invalid date.');
        }

        return $date;
    }
}
