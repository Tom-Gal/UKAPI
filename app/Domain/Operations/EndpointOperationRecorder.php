<?php

namespace App\Domain\Operations;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class EndpointOperationRecorder
{
    private const LATENCY_BUCKETS = [50, 100, 250, 500, 1_000, 2_000, 5_000, 15_000, 60_000];

    public function record(string $endpoint, int $status, int $latencyMs): void
    {
        if ($endpoint === '' || $endpoint === 'unknown') {
            return;
        }

        try {
            $at = CarbonImmutable::now('UTC');
            $latencyMs = max(0, min(60_000, $latencyMs));
            $bucket = $this->bucket($latencyMs);
            $key = [
                'date' => $at->toDateString(),
                'endpoint' => $endpoint,
                'status_family' => intdiv(max(100, min(599, $status)), 100).'xx',
                'latency_bucket_ms' => $bucket,
            ];
            DB::table('endpoint_daily_metrics')->insertOrIgnore([
                ...$key,
                'requests' => 0,
                'total_latency_ms' => 0,
                'max_latency_ms' => 0,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
            $row = DB::table('endpoint_daily_metrics')->where($key);
            $row->incrementEach([
                'requests' => 1,
                'total_latency_ms' => $latencyMs,
            ], [
                'updated_at' => $at,
            ]);
            (clone $row)->where('max_latency_ms', '<', $latencyMs)->update([
                'max_latency_ms' => $latencyMs,
                'updated_at' => $at,
            ]);
        } catch (\Throwable $exception) {
            // Operational telemetry must never change the customer response.
            report($exception);
        }
    }

    private function bucket(int $latencyMs): int
    {
        foreach (self::LATENCY_BUCKETS as $bucket) {
            if ($latencyMs <= $bucket) {
                return $bucket;
            }
        }

        return 60_000;
    }
}
