<?php

namespace App\Domain\Calendar;

use InvalidArgumentException;

final readonly class BankHolidayCalendar
{
    /** @param array<string, BankHolidayDivision> $divisions */
    public function __construct(public array $divisions) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $divisions = $data['divisions'] ?? null;

        if (! is_array($divisions)) {
            throw new InvalidArgumentException('Bank holiday calendar data is invalid.');
        }

        $normalised = [];
        foreach ($divisions as $key => $division) {
            if (! is_string($key) || ! is_array($division)) {
                throw new InvalidArgumentException('Bank holiday calendar division is invalid.');
            }

            $normalised[$key] = BankHolidayDivision::fromArray($division);
        }

        foreach (['england-and-wales', 'scotland', 'northern-ireland'] as $required) {
            if (! isset($normalised[$required])) {
                throw new InvalidArgumentException('Bank holiday calendar is missing a supported division.');
            }
        }

        return new self($normalised);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'divisions' => array_map(static fn (BankHolidayDivision $division): array => $division->toArray(), $this->divisions),
        ];
    }
}
