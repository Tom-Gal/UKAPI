<?php

namespace App\Domain\Flood;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class FloodReading
{
    public function __construct(
        public string $measureId,
        public string $recordedAt,
        public float $value,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromEnvironmentAgency(array $payload): self
    {
        $measureId = self::string($payload['measure'] ?? null);
        $recordedAt = self::timestamp($payload['dateTime'] ?? null);
        $value = $payload['value'] ?? null;

        if ($measureId === null || $recordedAt === null || ! is_numeric($value)) {
            throw new InvalidArgumentException('Environment Agency reading is invalid.');
        }

        return new self($measureId, $recordedAt, (float) $value);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $measureId = self::string($data['measure_id'] ?? null);
        $recordedAt = self::timestamp($data['recorded_at'] ?? null);
        $value = $data['value'] ?? null;

        if ($measureId === null || $recordedAt === null || ! is_numeric($value)) {
            throw new InvalidArgumentException('Cached flood reading is invalid.');
        }

        return new self($measureId, $recordedAt, (float) $value);
    }

    /** @return array{measure_id: string, recorded_at: string, value: float} */
    public function toArray(): array
    {
        return [
            'measure_id' => $this->measureId,
            'recorded_at' => $this->recordedAt,
            'value' => $this->value,
        ];
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function timestamp(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toIso8601String();
        } catch (\Exception) {
            return null;
        }
    }
}
