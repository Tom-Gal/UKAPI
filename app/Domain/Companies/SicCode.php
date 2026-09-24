<?php

namespace App\Domain\Companies;

use App\Support\Api\ApiException;

final readonly class SicCode
{
    private function __construct(private string $value) {}

    public static function from(string $value): self
    {
        $normalised = (string) preg_replace('/\s+/', '', trim($value));

        if (! self::isValid($normalised)) {
            throw ApiException::invalidSicCode();
        }

        return new self($normalised);
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match('/^\d{5}$/', $value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
