<?php

namespace App\Domain\Usage;

use Carbon\CarbonImmutable;

final class UsageInsights
{
    public function __construct(private readonly UsageMeter $usageMeter) {}

    /** @return array<string, mixed> */
    public function forUser(int $userId, int $quota, string $planKey): array
    {
        $now = CarbonImmutable::now('UTC');
        $month = $this->usageMeter->periodStart($now);
        $usage = $this->usageMeter->monthlyUsageForUser($userId, $month);
        $previousUsage = $this->usageMeter->monthlyUsageForUser($userId, $month->subMonth());
        $daysElapsed = $now->day;
        $daysInMonth = $now->daysInMonth;
        $dailyAverage = $daysElapsed > 0 ? $usage / $daysElapsed : 0.0;
        $projectedUsage = (int) round($dailyAverage * $daysInMonth);
        $usagePercentage = $quota > 0 ? (int) round(($usage / $quota) * 100) : 100;
        $notificationThreshold = $this->lowestNotificationThreshold();
        $rejections = $this->usageMeter->monthlyRejectionsForUser($userId, $month);
        $expectedQuotaDate = null;

        if ($dailyAverage > 0 && $projectedUsage >= $quota) {
            $quotaDay = max(1, (int) ceil($quota / $dailyAverage));
            $expectedQuotaDate = $month->addDays(min($daysInMonth, $quotaDay) - 1)->toDateString();
        }

        $dailyTrend = $this->usageMeter->dailyTrendForUser($userId, $month);

        return [
            'dailyTrend' => $dailyTrend,
            'dailyTrackingStartedAt' => $dailyTrend[0]['date'] ?? null,
            'rejections' => $rejections,
            'projection' => [
                'requests' => $projectedUsage,
                'percentage_of_quota' => $quota > 0 ? (int) round(($projectedUsage / $quota) * 100) : 100,
                'expected_quota_date' => $expectedQuotaDate,
                'days_elapsed' => $daysElapsed,
                'days_in_month' => $daysInMonth,
            ],
            'previousMonth' => [
                'requests' => $previousUsage,
                'difference' => $usage - $previousUsage,
                'percentage_change' => $previousUsage > 0
                    ? (int) round((($usage - $previousUsage) / $previousUsage) * 100)
                    : null,
                'label' => $month->subMonth()->format('F Y'),
            ],
            'upgradeRecommended' => $planKey !== 'scale' && (
                $usagePercentage >= $notificationThreshold
                || $projectedUsage >= $quota
                || $rejections['quota'] > 0
            ),
        ];
    }

    private function lowestNotificationThreshold(): int
    {
        $configured = config('ukapi.usage_notification_thresholds', '80,100');
        $values = is_array($configured) ? $configured : explode(',', (string) $configured);
        $thresholds = array_filter(
            array_map(static fn (mixed $value): int => (int) trim((string) $value), $values),
            static fn (int $value): bool => $value > 0 && $value <= 100,
        );

        return $thresholds === [] ? 80 : min($thresholds);
    }
}
