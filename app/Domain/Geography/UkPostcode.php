<?php

namespace App\Domain\Geography;

use App\Support\Api\ApiException;

final readonly class UkPostcode
{
    private const PATTERN = '/^(?:GIR0AA|(?:[A-PR-UWYZ][0-9][0-9A-HJKSTUW]?|[A-PR-UWYZ][A-HK-Y][0-9][0-9ABEHMNPRVWXY]?)[0-9][ABD-HJLNP-UW-Z]{2})$/';

    private function __construct(private string $compact) {}

    public static function from(string $value): self
    {
        $compact = strtoupper((string) preg_replace('/\\s+/', '', trim($value)));

        if ($compact === '' || ! preg_match(self::PATTERN, $compact)) {
            throw ApiException::invalidPostcode();
        }

        return new self($compact);
    }

    public function compact(): string
    {
        return $this->compact;
    }

    public function display(): string
    {
        return substr($this->compact, 0, -3).' '.substr($this->compact, -3);
    }
}
