<?php

namespace App\Domain\Companies;

use InvalidArgumentException;

final readonly class SicData
{
    public function __construct(
        public string $code,
        public string $description,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $code = $data['code'] ?? null;
        $description = $data['description'] ?? null;

        if (! is_string($code) || ! SicCode::isValid($code) || ! is_string($description) || trim($description) === '') {
            throw new InvalidArgumentException('SIC reference snapshot contains an invalid record.');
        }

        return new self($code, trim($description));
    }

    /** @return array{code: string, description: string} */
    public function toArray(): array
    {
        return ['code' => $this->code, 'description' => $this->description];
    }
}
