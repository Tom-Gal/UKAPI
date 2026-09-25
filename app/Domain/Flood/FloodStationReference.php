<?php

namespace App\Domain\Flood;

use App\Support\Api\ApiException;

final readonly class FloodStationReference
{
    private function __construct(private string $value) {}

    public static function from(string $value): self
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/', $value) !== 1) {
            throw ApiException::invalidParameter('id', 'The station id must contain only letters, numbers, hyphens or underscores.');
        }

        return new self(strtoupper($value));
    }

    public function value(): string
    {
        return $this->value;
    }
}
