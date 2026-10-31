<?php

namespace App\Domain\Geography;

use InvalidArgumentException;

final readonly class CouncilData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $nameCy,
        public string $country,
        public string $referenceVersion,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $code = $data['code'] ?? null;
        $name = $data['name'] ?? null;
        $version = $data['reference_version'] ?? null;

        if (! is_string($code) || ! is_string($name) || ! is_string($version)) {
            throw new InvalidArgumentException('Council data is incomplete.');
        }

        $identifier = UkCouncilCode::from($code);
        $nameCy = $data['name_cy'] ?? null;

        return new self(
            code: $identifier->value(),
            name: $name,
            nameCy: is_string($nameCy) && $nameCy !== '' ? $nameCy : null,
            country: is_string($data['country'] ?? null) ? $data['country'] : $identifier->country(),
            referenceVersion: $version,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'name_cy' => $this->nameCy,
            'country' => $this->country,
            'reference_version' => $this->referenceVersion,
        ];
    }
}
