<?php

namespace App\Domain\Companies;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class CompanyData
{
    /**
     * @param  list<string>  $sicCodes
     * @param  array{next_due: ?string, next_made_up_to: ?string, last_made_up_to: ?string}  $accounts
     * @param  array{next_due: ?string, next_made_up_to: ?string, last_made_up_to: ?string}  $confirmationStatement
     */
    public function __construct(
        public string $companyNumber,
        public string $name,
        public ?string $status,
        public ?string $type,
        public ?string $jurisdiction,
        public ?string $incorporatedOn,
        public ?string $dissolvedOn,
        public ?CompanyAddress $registeredOfficeAddress,
        public array $sicCodes,
        public ?bool $hasBeenLiquidated,
        public array $accounts,
        public array $confirmationStatement,
    ) {}

    /** @param array<string, mixed> $company */
    public static function fromCompaniesHouse(array $company): self
    {
        $companyNumber = self::requiredString($company, 'company_number');
        $normalisedNumber = strtoupper($companyNumber);

        if (! CompanyNumber::isValid($normalisedNumber)) {
            throw new InvalidArgumentException('Companies House returned an invalid company number.');
        }

        $address = is_array($company['registered_office_address'] ?? null)
            ? CompanyAddress::fromCompaniesHouse($company['registered_office_address'])
            : null;
        $accounts = is_array($company['accounts'] ?? null) ? $company['accounts'] : [];
        $confirmation = is_array($company['confirmation_statement'] ?? null) ? $company['confirmation_statement'] : [];

        return new self(
            companyNumber: $normalisedNumber,
            name: self::requiredString($company, 'company_name'),
            status: self::string($company['company_status'] ?? null),
            type: self::string($company['type'] ?? null),
            jurisdiction: self::string($company['jurisdiction'] ?? null),
            incorporatedOn: self::date($company['date_of_creation'] ?? null),
            dissolvedOn: self::date($company['date_of_cessation'] ?? null),
            registeredOfficeAddress: $address,
            sicCodes: self::stringList($company['sic_codes'] ?? null),
            hasBeenLiquidated: is_bool($company['has_been_liquidated'] ?? null) ? $company['has_been_liquidated'] : null,
            accounts: self::dates($accounts),
            confirmationStatement: self::dates($confirmation),
        );
    }

    /** @param array<string, mixed> $company */
    public static function fromArray(array $company): self
    {
        $address = is_array($company['registered_office_address'] ?? null)
            ? CompanyAddress::fromArray($company['registered_office_address'])
            : null;

        return new self(
            companyNumber: self::requiredString($company, 'company_number'),
            name: self::requiredString($company, 'name'),
            status: self::string($company['status'] ?? null),
            type: self::string($company['type'] ?? null),
            jurisdiction: self::string($company['jurisdiction'] ?? null),
            incorporatedOn: self::date($company['incorporated_on'] ?? null),
            dissolvedOn: self::date($company['dissolved_on'] ?? null),
            registeredOfficeAddress: $address,
            sicCodes: self::stringList($company['sic_codes'] ?? null),
            hasBeenLiquidated: is_bool($company['has_been_liquidated'] ?? null) ? $company['has_been_liquidated'] : null,
            accounts: self::dates(is_array($company['accounts'] ?? null) ? $company['accounts'] : []),
            confirmationStatement: self::dates(is_array($company['confirmation_statement'] ?? null) ? $company['confirmation_statement'] : []),
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
            'jurisdiction' => $this->jurisdiction,
            'incorporated_on' => $this->incorporatedOn,
            'dissolved_on' => $this->dissolvedOn,
            'registered_office_address' => $this->registeredOfficeAddress?->toArray(),
            'sic_codes' => $this->sicCodes,
            'has_been_liquidated' => $this->hasBeenLiquidated,
            'accounts' => $this->accounts,
            'confirmation_statement' => $this->confirmationStatement,
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

    /** @return list<string> */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => is_string($item) && $item !== ''));
    }

    /**
     * @param  array<string, mixed>  $dates
     * @return array{next_due: ?string, next_made_up_to: ?string, last_made_up_to: ?string}
     */
    private static function dates(array $dates): array
    {
        return [
            'next_due' => self::date($dates['next_due'] ?? null),
            'next_made_up_to' => self::date($dates['next_made_up_to'] ?? null),
            'last_made_up_to' => self::date($dates['last_made_up_to'] ?? null),
        ];
    }
}
