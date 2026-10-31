<?php

namespace App\Domain\Geography;

use App\Support\Api\ApiException;

final readonly class UkCouncilCode
{
    private function __construct(private string $value) {}

    public static function from(string $value): self
    {
        $normalised = strtoupper(trim($value));

        if (! preg_match('/^[A-Z]\d{8}$/', $normalised)) {
            throw ApiException::invalidCouncilCode();
        }

        return new self($normalised);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function country(): string
    {
        return match ($this->value[0]) {
            'E' => 'England',
            'W' => 'Wales',
            'S' => 'Scotland',
            'N' => 'Northern Ireland',
            default => 'United Kingdom',
        };
    }
}
