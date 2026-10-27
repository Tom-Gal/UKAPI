<?php

namespace App\Domain\Operations;

use App\Models\User;
use Illuminate\Contracts\Cache\Repository;

final class LoginAlertDecider
{
    public function __construct(private readonly Repository $cache) {}

    public function shouldNotify(User $user, ?string $ipAddress, ?string $userAgent): bool
    {
        $fingerprint = hash('sha256', implode('|', [
            trim(mb_strtolower((string) $ipAddress)),
            trim(mb_strtolower((string) $userAgent)),
        ]));
        $key = sprintf('ukapi:security:login-alert:%d:%s', $user->id, $fingerprint);

        try {
            return $this->cache->add(
                $key,
                true,
                now()->addDays(max(1, (int) config('ukapi.notifications.login_deduplication_days', 30))),
            );
        } catch (\Throwable $exception) {
            // A cache outage must not suppress a potentially important account alert.
            report($exception);

            return true;
        }
    }
}
