<?php

namespace App\Domain\Crime;

use App\Support\Api\ApiException;
use Carbon\CarbonImmutable;

final readonly class YearMonth
{
    private function __construct(private string $value) {}

    public static function from(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('!Y-m', $value);

        if ($date === null || $date->format('Y-m') !== $value || $date->isFuture()) {
            throw ApiException::invalidParameter('month', 'Month must use the YYYY-MM format and cannot be in the future.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
