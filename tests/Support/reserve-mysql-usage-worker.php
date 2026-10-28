<?php

use App\Domain\Usage\UsageMeter;
use App\Models\ApiKey;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('testing') || DB::connection()->getDriverName() !== 'mysql') {
    throw new RuntimeException('MySQL usage test worker requires the testing MySQL connection.');
}

$key = ApiKey::query()->findOrFail((int) $argv[1]);
$meter = new UsageMeter;

while (microtime(true) < (float) $argv[2]) {
    usleep(1000);
}

$admitted = 0;
for ($i = 0; $i < 5; $i++) {
    if ($meter->reserve($key, 'vat.calculate', 7, CarbonImmutable::parse('2026-10-22T12:00:00Z')) !== null) {
        $admitted++;
    }
}

echo json_encode(['admitted' => $admitted], JSON_THROW_ON_ERROR);
