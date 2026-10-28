<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class MySqlUsageConcurrencyTest extends TestCase
{
    public function test_mysql_processes_share_one_hard_account_quota(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This integration test requires MySQL.');
        }

        $user = User::factory()->create();
        $keys = [];
        foreach (range(1, 2) as $index) {
            $keys[] = $user->apiKeys()->create([
                'public_id' => 'k_mysql_concurrency_'.$index.'_'.Str::lower(Str::random(8)),
                'secret_hash' => 'unused-by-meter-test',
                'name' => 'MySQL concurrency '.$index,
                'environment' => 'test',
                'status' => 'active',
            ]);
        }

        $workers = [];
        try {
            $startAt = (string) (microtime(true) + 3);
            foreach ($keys as $key) {
                $worker = new Process([
                    PHP_BINARY,
                    base_path('tests/Support/reserve-mysql-usage-worker.php'),
                    (string) $key->id,
                    $startAt,
                ], base_path(), [
                    'APP_ENV' => 'testing',
                    'APP_CONFIG_CACHE' => base_path('bootstrap/cache/no-mysql-test-config.php'),
                    'CACHE_STORE' => 'array',
                    'DB_CONNECTION' => 'mysql',
                    'DB_HOST' => (string) config('database.connections.mysql.host'),
                    'DB_PORT' => (string) config('database.connections.mysql.port'),
                    'DB_DATABASE' => (string) config('database.connections.mysql.database'),
                    'DB_USERNAME' => (string) config('database.connections.mysql.username'),
                    'DB_PASSWORD' => (string) config('database.connections.mysql.password'),
                ]);
                $worker->setTimeout(30)->start();
                $workers[] = $worker;
            }

            $admitted = 0;
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput().$worker->getOutput());
                $output = trim($worker->getOutput());
                $this->assertJson($output, $worker->getErrorOutput().$output);
                $admitted += json_decode($output, true, flags: JSON_THROW_ON_ERROR)['admitted'];
            }

            $this->assertSame(7, $admitted);
            $this->assertSame(7, (int) DB::table('monthly_usage')->where('user_id', $user->id)->value('requests'));
            $this->assertSame(7, (int) DB::table('monthly_endpoint_usage')->where('user_id', $user->id)
                ->where('endpoint', 'vat.calculate')->value('requests'));
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }

            $user->delete();
        }
    }
}
