<?php

namespace App\Domain\Calendar;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class BankHoliday
{
    public function __construct(
        public string $date,
        public string $title,
        public ?string $notes,
        public bool $bunting,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $date = $data['date'] ?? null;
        $title = $data['title'] ?? null;

        $parsedDate = is_string($date) ? CarbonImmutable::createFromFormat('!Y-m-d', $date) : false;

        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || ! $parsedDate instanceof CarbonImmutable || $parsedDate->format('Y-m-d') !== $date
            || ! is_string($title) || $title === '') {
            throw new InvalidArgumentException('Bank holiday data is invalid.');
        }

        $notes = $data['notes'] ?? null;

        return new self(
            date: $date,
            title: $title,
            notes: is_string($notes) && $notes !== '' ? $notes : null,
            bunting: (bool) ($data['bunting'] ?? false),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'title' => $this->title,
            'notes' => $this->notes,
            'bunting' => $this->bunting,
        ];
    }
}
