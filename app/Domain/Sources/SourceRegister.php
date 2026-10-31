<?php

namespace App\Domain\Sources;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class SourceRegister
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return array_values(config('source_reviews.sources', []));
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     docs_url: string,
     *     licence_url: string,
     *     coverage: string,
     *     used_for: string,
     *     licence_summary: string,
     *     attribution: string
     * }>
     */
    public function publicSources(): array
    {
        return array_map(
            static fn (array $source): array => [
                'key' => (string) $source['key'],
                'label' => (string) $source['label'],
                'docs_url' => (string) $source['docs_url'],
                'licence_url' => (string) $source['licence_url'],
                'coverage' => (string) $source['coverage'],
                'used_for' => (string) $source['used_for'],
                'licence_summary' => (string) $source['licence_summary'],
                'attribution' => (string) $source['attribution'],
            ],
            $this->all(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function due(?CarbonImmutable $asOf = null): array
    {
        $asOf ??= CarbonImmutable::now();

        return array_values(array_filter(
            $this->all(),
            fn (array $source): bool => $this->dueAt($source)->lessThanOrEqualTo($asOf),
        ));
    }

    /** @param array<string, mixed> $source */
    public function dueAt(array $source): CarbonImmutable
    {
        $lastReviewedAt = $source['last_reviewed_at'] ?? null;
        $intervalDays = max(1, (int) ($source['review_interval_days'] ?? 90));

        if (! is_string($lastReviewedAt) || $lastReviewedAt === '') {
            return CarbonImmutable::create(1970, 1, 1);
        }

        try {
            return CarbonImmutable::parse($lastReviewedAt)->addDays($intervalDays);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Source review date is invalid.', previous: $exception);
        }
    }

    /** @param array<string, mixed> $source */
    public function reviewStatus(array $source, ?CarbonImmutable $asOf = null): string
    {
        $asOf ??= CarbonImmutable::now();

        return $this->dueAt($source)->lessThanOrEqualTo($asOf) ? 'due' : 'current';
    }
}
