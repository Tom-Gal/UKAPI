<?php

namespace App\Domain\Companies;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class OfficerData
{
    public function __construct(
        public string $name,
        public string $role,
        public ?string $appointedOn,
        public ?string $resignedOn,
        public ?string $nationality,
        public ?string $countryOfResidence,
        public ?string $occupation,
    ) {}

    /** @param array<string, mixed> $officer */
    public static function fromCompaniesHouse(array $officer): self
    {
        return new self(
            name: self::requiredString($officer, 'name'),
            role: self::requiredString($officer, 'officer_role'),
            appointedOn: self::date($officer['appointed_on'] ?? null),
            resignedOn: self::date($officer['resigned_on'] ?? null),
            nationality: self::string($officer['nationality'] ?? null),
            countryOfResidence: self::string($officer['country_of_residence'] ?? null),
            occupation: self::string($officer['occupation'] ?? null),
        );
    }

    /** @param array<string, mixed> $officer */
    public static function fromArray(array $officer): self
    {
        return new self(
            name: self::requiredString($officer, 'name'),
            role: self::requiredString($officer, 'role'),
            appointedOn: self::date($officer['appointed_on'] ?? null),
            resignedOn: self::date($officer['resigned_on'] ?? null),
            nationality: self::string($officer['nationality'] ?? null),
            countryOfResidence: self::string($officer['country_of_residence'] ?? null),
            occupation: self::string($officer['occupation'] ?? null),
        );
    }

    /** @return array<string, ?string> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'role' => $this->role,
            'appointed_on' => $this->appointedOn,
            'resigned_on' => $this->resignedOn,
            'nationality' => $this->nationality,
            'country_of_residence' => $this->countryOfResidence,
            'occupation' => $this->occupation,
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
