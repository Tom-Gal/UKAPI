<?php

namespace App\Domain\Calendar;

use InvalidArgumentException;

final readonly class BankHolidayDivision
{
    /** @param list<BankHoliday> $events */
    public function __construct(
        public string $division,
        public array $events,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $division = $data['division'] ?? null;
        $events = $data['events'] ?? null;

        if (! is_string($division) || ! self::isSupported($division) || ! is_array($events)) {
            throw new InvalidArgumentException('Bank holiday division data is invalid.');
        }

        $normalised = [];
        foreach ($events as $event) {
            if (! is_array($event)) {
                throw new InvalidArgumentException('Bank holiday event data is invalid.');
            }

            $normalised[] = BankHoliday::fromArray($event);
        }

        return new self($division, $normalised);
    }

    public static function isSupported(string $division): bool
    {
        return in_array($division, ['england-and-wales', 'scotland', 'northern-ireland'], true);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'division' => $this->division,
            'events' => array_map(static fn (BankHoliday $event): array => $event->toArray(), $this->events),
        ];
    }
}
