<?php

namespace App\Domain\Companies;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class SicSnapshot
{
    /** @param list<SicData> $records */
    public function __construct(
        public string $version,
        public string $retrievedAt,
        public string $checksum,
        public array $records,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $version = $payload['version'] ?? null;
        $retrievedAt = $payload['retrieved_at'] ?? null;
        $checksum = $payload['checksum'] ?? null;
        $records = $payload['records'] ?? null;

        if (! is_string($version) || trim($version) === '' || ! is_string($retrievedAt)
            || ! self::isIso8601($retrievedAt) || ! is_string($checksum) || ! preg_match('/^[a-f0-9]{64}$/', $checksum)
            || ! is_array($records)) {
            throw new InvalidArgumentException('SIC reference snapshot is invalid.');
        }

        $normalised = [];
        foreach ($records as $record) {
            if (! is_array($record)) {
                throw new InvalidArgumentException('SIC reference snapshot contains an invalid record.');
            }

            $normalised[] = SicData::fromArray($record);
        }

        if ($normalised === []) {
            throw new InvalidArgumentException('SIC reference snapshot is empty.');
        }

        return new self(trim($version), $retrievedAt, $checksum, $normalised);
    }

    public function find(SicCode $code): ?SicData
    {
        foreach ($this->records as $record) {
            if ($record->code === $code->value()) {
                return $record;
            }
        }

        return null;
    }

    /** @return list<SicData> */
    public function search(string $query): array
    {
        $needle = mb_strtolower(trim($query));

        return array_values(array_filter(
            $this->records,
            static fn (SicData $record): bool => str_contains($record->code, $needle)
                || str_contains(mb_strtolower($record->description), $needle),
        ));
    }

    private static function isIso8601(string $value): bool
    {
        return DateTimeImmutable::createFromFormat(DATE_ATOM, $value) !== false;
    }
}
