<?php

namespace App\Http\Controllers;

use App\Domain\Usage\UsageMeter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UsageExportController extends Controller
{
    public function __invoke(Request $request, UsageMeter $usageMeter): StreamedResponse
    {
        $attributes = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = isset($attributes['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $attributes['month'], 'UTC')
            : $usageMeter->periodStart();
        $earliest = $usageMeter->periodStart()->subMonths(11);
        abort_if($month->isFuture() || $month->lt($earliest), 422, 'Month must be within the last 12 calendar months.');

        $userId = (int) $request->user()->id;
        $summary = $usageMeter->monthlyUsageForUser($userId, $month);
        $rejections = $usageMeter->monthlyRejectionsForUser($userId, $month);
        $trend = $usageMeter->dailyTrendForUser($userId, $month);
        $breakdown = $usageMeter->monthlyBreakdownForUser($userId, $month);
        $filename = 'ukapi-usage-'.$month->format('Y-m').'.csv';

        return response()->streamDownload(function () use ($month, $summary, $rejections, $trend, $breakdown): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['record_type', 'period_or_date', 'endpoint', 'label', 'requests', 'rate_limit_rejections', 'quota_rejections']);
            fputcsv($output, ['summary', $month->format('Y-m'), '', 'All admitted requests', $summary, $rejections['rate_limit'], $rejections['quota']]);
            foreach ($trend as $day) {
                fputcsv($output, ['daily', $day['date'], '', 'Daily total', $day['requests'], $day['rate_limit_rejections'], $day['quota_rejections']]);
            }
            foreach ($breakdown as $item) {
                fputcsv($output, ['endpoint', $month->format('Y-m'), $item['endpoint'], $item['label'], $item['requests'], '', '']);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
