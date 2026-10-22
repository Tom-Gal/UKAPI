<?php

use App\Domain\Usage\UsageMeter;
use App\Models\ApiKey;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// This worker is only used against the isolated database created by the test.
if (! $app->environment('testing')) {
    throw new RuntimeException('Usage test worker requires APP_ENV=testing.');
}
config([
    'database.default' => 'usage_concurrency',
    'database.connections.usage_concurrency' => [
        'driver' => 'sqlite',
        'database' => $argv[1],
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => 15000,
    ],
]);
DB::purge('usage_concurrency');
$key = ApiKey::query()->findOrFail((int) $argv[2]);
$meter = new UsageMeter;
while (microtime(true) < (float) $argv[3]) {
    usleep(1000);
}
$admitted = 0;
for ($i = 0; $i < 5; $i++) {
    if ($meter->reserve($key, 'vat.calculate', 7, CarbonImmutable::parse('2026-10-22T12:00:00Z')) !== null) {
        $admitted++;
    }
}
echo json_encode(['admitted' => $admitted], JSON_THROW_ON_ERROR);
