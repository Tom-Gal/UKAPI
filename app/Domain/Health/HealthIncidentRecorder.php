<?php

namespace App\Domain\Health;

use App\Models\ServiceHealthCheck;
use App\Models\ServiceHealthIncident;
use Illuminate\Support\Facades\DB;

final class HealthIncidentRecorder
{
    public function record(ServiceHealthCheck $check): void
    {
        DB::transaction(function () use ($check): void {
            $open = ServiceHealthIncident::query()
                ->where('service', $check->service)
                ->whereNull('recovered_at')
                ->lockForUpdate()
                ->latest('started_at')
                ->first();

            if ($check->status === 'available') {
                if ($open) {
                    $open->forceFill([
                        'last_observed_at' => $check->last_checked_at ?? now(),
                        'recovered_at' => $check->last_checked_at ?? now(),
                        'latest_latency_ms' => $check->latency_ms,
                    ])->save();
                }

                return;
            }

            if ($check->status !== 'unavailable') {
                return;
            }

            $reason = $check->failure_reason ?? 'probe_failed';
            if ($open && $open->failure_reason === $reason) {
                $open->forceFill([
                    'last_observed_at' => $check->last_checked_at ?? now(),
                    'occurrences' => min(4_294_967_295, $open->occurrences + 1),
                    'latest_latency_ms' => $check->latency_ms,
                ])->save();

                return;
            }

            if ($open) {
                $open->forceFill(['recovered_at' => $check->last_checked_at ?? now()])->save();
            }

            ServiceHealthIncident::query()->create([
                'service' => $check->service,
                'failure_reason' => $reason,
                'started_at' => $check->last_checked_at ?? now(),
                'last_observed_at' => $check->last_checked_at ?? now(),
                'occurrences' => 1,
                'latest_latency_ms' => $check->latency_ms,
            ]);
        }, 3);
    }
}
