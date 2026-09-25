<?php

namespace App\Domain\Crime;

use InvalidArgumentException;

final readonly class CrimeCategory
{
    public function __construct(
        public string $code,
        public string $name,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromPoliceUk(array $payload): self
    {
        $code = $payload['url'] ?? null;
        $name = $payload['name'] ?? null;

        if (! is_string($code) || $code === '' || ! is_string($name) || $name === '') {
            throw new InvalidArgumentException('Police.uk category response is invalid.');
        }

        return new self($code, $name);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $code = $data['code'] ?? null;
        $name = $data['name'] ?? null;

        if (! is_string($code) || $code === '' || ! is_string($name) || $name === '') {
            throw new InvalidArgumentException('Cached crime category data is invalid.');
        }

        return new self($code, $name);
    }

    /** @return array{code: string, name: string} */
    public function toArray(): array
    {
        return ['code' => $this->code, 'name' => $this->name];
    }
}
