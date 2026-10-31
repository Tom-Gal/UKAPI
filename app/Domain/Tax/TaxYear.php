<?php

namespace App\Domain\Tax;

use App\Support\Api\ApiException;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class TaxYear
{
    public function __construct(
        public string $year,
        public string $startsOn,
        public string $endsOn,
    ) {}

    public static function fromYear(string $year): self
    {
        if (! preg_match('/^\d{4}$/', $year)) {
            throw ApiException::invalidParameter('year', 'The tax year must begin with a four-digit calendar year.');
        }

        $startYear = (int) $year;
        $start = CarbonImmutable::create($startYear, 4, 6, 0, 0, 0);

        if (! $start instanceof CarbonImmutable) {
            throw new InvalidArgumentException('Tax year start date is invalid.');
        }

        return new self(
            year: sprintf('%d-%02d', $startYear, ($startYear + 1) % 100),
            startsOn: $start->format('Y-m-d'),
            endsOn: $start->addYear()->subDay()->format('Y-m-d'),
        );
    }

    public static function current(): self
    {
        $today = now();
        $startYear = (int) $today->format('Y');

        if ($today->lt(CarbonImmutable::create($startYear, 4, 6, 0, 0, 0))) {
            $startYear--;
        }

        return self::fromYear((string) $startYear);
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'tax_year' => $this->year,
            'starts_on' => $this->startsOn,
            'ends_on' => $this->endsOn,
        ];
    }
}
