<?php

namespace App\Domain\Operations;

use App\Notifications\QueueHealthAlert;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class QueueHealthMonitor
{
    private const INCIDENT_CACHE_KEY = 'ukapi:operations:queue-health-incident';

    public function __construct(private readonly Repository $cache) {}

    /**
     * @return array{status: string, pending: int, failed: int, oldest_age_seconds: int|null, alerted: bool}
     */
    public function check(): array
    {
        $pending = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();
        $oldestCreatedAt = DB::table('jobs')->min('created_at');
        $oldestAgeSeconds = is_numeric($oldestCreatedAt)
            ? max(0, now()->getTimestamp() - (int) $oldestCreatedAt)
            : null;
        $degraded = $failed > 0
            || $pending > max(0, (int) config('ukapi.operations.queue_monitor.max_pending', 100))
            || $oldestAgeSeconds !== null
                && $oldestAgeSeconds > max(1, (int) config('ukapi.operations.queue_monitor.max_oldest_seconds', 300));

        $incident = $this->incident();
        if (! $degraded) {
            $alerted = false;
            if ($incident !== null) {
                $alerted = $this->sendAlert(true, $pending, $failed, $oldestAgeSeconds);
                if ($alerted) {
                    $this->cache->forget(self::INCIDENT_CACHE_KEY);
                }
            }

            return $this->result('healthy', $pending, $failed, $oldestAgeSeconds, $alerted);
        }

        if ($incident !== null && ! $this->repeatIsDue($incident)) {
            return $this->result('degraded', $pending, $failed, $oldestAgeSeconds, false);
        }

        $alerted = $this->sendAlert(false, $pending, $failed, $oldestAgeSeconds);
        if ($alerted) {
            $this->cache->forever(self::INCIDENT_CACHE_KEY, [
                'started_at' => $incident['started_at'] ?? now()->getTimestamp(),
                'last_alert_at' => now()->getTimestamp(),
            ]);
        }

        return $this->result('degraded', $pending, $failed, $oldestAgeSeconds, $alerted);
    }

    /** @return array{started_at: int, last_alert_at: int}|null */
    private function incident(): ?array
    {
        $value = $this->cache->get(self::INCIDENT_CACHE_KEY);

        return is_array($value)
            && is_int($value['started_at'] ?? null)
            && is_int($value['last_alert_at'] ?? null)
                ? $value
                : null;
    }

    /** @param array{started_at: int, last_alert_at: int} $incident */
    private function repeatIsDue(array $incident): bool
    {
        $minutes = max(0, (int) config('ukapi.operations.queue_monitor.repeat_after_minutes', 60));

        return $minutes > 0 && $incident['last_alert_at'] <= now()->subMinutes($minutes)->getTimestamp();
    }

    private function sendAlert(bool $recovered, int $pending, int $failed, ?int $oldestAgeSeconds): bool
    {
        $email = config('health.alerts.email');
        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        try {
            Notification::route('mail', $email)->notifyNow(new QueueHealthAlert(
                recovered: $recovered,
                pending: $pending,
                failed: $failed,
                oldestAgeSeconds: $oldestAgeSeconds,
            ));

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * @return array{status: string, pending: int, failed: int, oldest_age_seconds: int|null, alerted: bool}
     */
    private function result(string $status, int $pending, int $failed, ?int $oldestAgeSeconds, bool $alerted): array
    {
        return [
            'status' => $status,
            'pending' => $pending,
            'failed' => $failed,
            'oldest_age_seconds' => $oldestAgeSeconds,
            'alerted' => $alerted,
        ];
    }
}
