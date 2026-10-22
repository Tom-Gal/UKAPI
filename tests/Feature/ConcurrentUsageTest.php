<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrentUsageTest extends TestCase
{
    public function test_independent_processes_share_one_hard_quota(): void
    {
        $database = tempnam(sys_get_temp_dir(), 'ukapi-quota-');
        $this->assertNotFalse($database);
        $previous = config('database.default');
        config([
            'database.default' => 'usage_concurrency',
            'database.connections.usage_concurrency' => [
                'driver' => 'sqlite',
                'database' => $database,
                'prefix' => '',
                'foreign_key_constraints' => true,
                'busy_timeout' => 15000,
            ],
        ]);
        $workers = [];
        try {
            Artisan::call('migrate', ['--database' => 'usage_concurrency', '--force' => true]);
            $user = User::factory()->create();
            // Distinct keys must still compete for the same account allowance.
            $keys = [];
            foreach (range(1, 6) as $i) {
                $keys[] = $user->apiKeys()->create([
                    'public_id' => 'k_concurrency'.$i,
                    'secret_hash' => 'unused-by-meter-test',
                    'name' => 'Concurrent '.$i,
                    'environment' => 'test',
                    'status' => 'active',
                ]);
            }
            $startAt = (string) (microtime(true) + 3);
            foreach ($keys as $key) {
                $worker = new Process([
                    PHP_BINARY, base_path('tests/Support/reserve-usage-worker.php'),
                    $database, (string) $key->id, $startAt,
                ], base_path(), ['APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => base_path('bootstrap/cache/no-test-config.php')]);
                $worker->setTimeout(30)->start();
                $workers[] = $worker;
            }
            $admitted = 0;
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput().$worker->getOutput());
                $admitted += json_decode($worker->getOutput(), true, flags: JSON_THROW_ON_ERROR)['admitted'];
            }
            $this->assertSame(7, $admitted);
            $this->assertSame(7, (int) DB::table('monthly_usage')->value('requests'));
            $this->assertSame(7, (int) DB::table('monthly_endpoint_usage')->value('requests'));
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
            DB::purge('usage_concurrency');
            config(['database.default' => $previous]);
            unlink($database);
        }
    }
}
