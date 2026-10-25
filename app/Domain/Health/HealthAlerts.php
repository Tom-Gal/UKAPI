<?php

namespace App\Domain\Health;

use App\Models\ServiceHealthCheck;
use Illuminate\Support\Facades\Notification;

class HealthAlerts
{
    public function send(ServiceHealthCheck $check): void
    {
        $email = config('health.alerts.email');
        if (! config('health.alerts.enabled') || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        if ($check->status === 'available') {
            if ($check->incident_notified_at !== null) {
                if (config('health.alerts.recovery')) {
                    Notification::route('mail', $email)->notify(new HealthAlert($check->service, true));
                }
                $check->forceFill(['incident_notified_at' => null, 'last_alert_at' => null])->save();
            }

            return;
        }

        if ($check->status !== 'unavailable'
            || $check->consecutive_failures < max(1, (int) config('health.alerts.failure_threshold'))) {
            return;
        }
        $repeatMinutes = max(0, (int) config('health.alerts.repeat_after_minutes'));
        if ($check->incident_notified_at !== null
            && ($repeatMinutes === 0 || $check->last_alert_at?->gt(now()->subMinutes($repeatMinutes)))) {
            return;
        }

        // Mark only after mail transport accepts the message, so failed delivery is retried.
        Notification::route('mail', $email)->notify(new HealthAlert($check->service, false, $check->failure_reason));
        $check->forceFill([
            'incident_notified_at' => $check->incident_notified_at ?? now(),
            'last_alert_at' => now(),
        ])->save();
    }
}
