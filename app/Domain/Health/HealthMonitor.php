<?php

namespace App\Domain\Health;

use App\Models\ServiceHealthCheck;

final class HealthMonitor
{
    public function __construct(private readonly HealthProbe $probe, private readonly HealthAlerts $alerts) {}

    /** @return array<string, string> */
    public function run(): array
    {
        $outcomes = [];
        foreach (HealthStatus::SERVICES as $service => $name) {
            $check = ServiceHealthCheck::firstOrCreate(['service' => $service]);
            // Persist before network I/O: a killed process leaves an honest incomplete attempt.
            $check->forceFill(['last_attempt_at' => now(), 'status' => 'unknown'])->save();
            try {
                $result = $this->probe->check($service);
                $check->forceFill([
                    'status' => 'available',
                    'failure_reason' => null,
                    'last_success_at' => now(),
                    'source_date' => $result->sourceDate,
                    'reference_retrieved_at' => $result->referenceRetrievedAt,
                    'consecutive_failures' => 0,
                ]);
            } catch (ProbeFailure $exception) {
                $check->forceFill([
                    'status' => 'unavailable',
                    'failure_reason' => $exception->reason,
                    'consecutive_failures' => min(4_294_967_295, $check->consecutive_failures + 1),
                ]);
            } catch (\Throwable) {
                $check->forceFill([
                    'status' => 'unavailable',
                    'failure_reason' => 'probe_failed',
                    'consecutive_failures' => min(4_294_967_295, $check->consecutive_failures + 1),
                ]);
            }
            $check->last_checked_at = now();
            $check->save();
            $outcomes[$service] = $check->status;

            try {
                $this->alerts->send($check);
            } catch (\Throwable) {
                // Delivery failures must not erase measurements or prevent later probes.
                $outcomes[$service] = 'alert_delivery_failed';
            }
        }

        return $outcomes;
    }
}
