<?php

namespace App\Domain\Companies;

use App\Support\Api\ApiException;

final readonly class CompanyNumber
{
    private function __construct(private string $value) {}

    public static function from(string $value): self
    {
        $normalised = strtoupper((string) preg_replace('/\\s+/', '', trim($value)));

        if (! self::isValid($normalised)) {
            throw ApiException::invalidCompanyNumber();
        }

        return new self($normalised);
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match('/^(?=.*\\d)[A-Z0-9]{1,8}$/', $value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
