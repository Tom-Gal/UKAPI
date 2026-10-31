<?php

namespace App\Domain\Geography;

use App\Support\Api\ApiException;

final readonly class UkOutcode
{
    private const PATTERN = '/^(?:GIR0|[A-PR-UWYZ][A-Z]?\d{1,2}[A-Z]?)$/';

    private function __construct(private string $value) {}

    public static function from(string $value): self
    {
        $normalised = strtoupper((string) preg_replace('/\s+/', '', trim($value)));

        if ($normalised === '' || ! preg_match(self::PATTERN, $normalised)) {
            throw ApiException::invalidOutcode();
        }

        return new self($normalised);
    }

    public function value(): string
    {
        return $this->value;
    }
}
