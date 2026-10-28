<?php

namespace App\Http\Controllers;

use App\Domain\Health\HealthMonitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

final class AdminTestingController extends Controller
{
    public function health(HealthMonitor $monitor): RedirectResponse
    {
        if (! config('health.enabled')) {
            return to_route('admin.testing')->with('status', 'Health monitoring is disabled.');
        }

        $lock = Cache::lock('ukapi:health:monitor', 300);
        if (! $lock->get()) {
            return to_route('admin.testing')->with('status', 'A health check is already running.');
        }

        try {
            $results = $monitor->run();
            $failed = count(array_filter($results, static fn (string $result): bool => $result !== 'available'));

            return to_route('admin.testing')
                ->with('health_results', $results)
                ->with('status', $failed === 0
                    ? 'All configured health probes passed.'
                    : "Health probes completed with {$failed} service(s) requiring attention.");
        } catch (\Throwable $exception) {
            report($exception);

            return to_route('admin.testing')->with(
                'status',
                'Health probes could not complete. Check the stored service observations and application logs.',
            );
        } finally {
            $lock->release();
        }
    }
}
