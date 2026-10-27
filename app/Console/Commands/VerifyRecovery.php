<?php

namespace App\Console\Commands;

use App\Domain\Operations\RecoveryManifest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class VerifyRecovery extends Command
{
    protected $signature = 'ukapi:recovery:verify
        {--baseline= : Compare checks with a manifest JSON file}
        {--expect-checksum= : Compare with a checks-only SHA-256 checksum}
        {--checksum-only : Print only the checks checksum and result}
        {--write= : Write the generated manifest to this path}';

    protected $description = 'Generate or compare a privacy-safe recovery manifest for a restored database.';

    public function handle(RecoveryManifest $manifests): int
    {
        try {
            $manifest = $manifests->generate();
            $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
            $checksChecksum = hash('sha256', json_encode($manifest['checks'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('The recovery manifest could not be generated.');

            return self::FAILURE;
        }

        $writePath = $this->option('write');
        if (is_string($writePath) && trim($writePath) !== '') {
            File::ensureDirectoryExists(dirname($writePath));
            File::put($writePath, $json);
            $this->info('Recovery manifest written.');
        } elseif (! $this->option('checksum-only')) {
            $this->line($json);
        }

        $this->line('Recovery checksum: '.$checksChecksum);

        $baselinePath = $this->option('baseline');
        if (is_string($baselinePath) && trim($baselinePath) !== '') {
            $comparison = $this->compare($baselinePath, $manifest);
            if (! $comparison) {
                return self::FAILURE;
            }
        }

        $expectedChecksum = $this->option('expect-checksum');
        if (is_string($expectedChecksum) && trim($expectedChecksum) !== '') {
            if (! hash_equals(mb_strtolower(trim($expectedChecksum)), $checksChecksum)) {
                $this->error('The restored database does not match the expected recovery checksum.');

                return self::FAILURE;
            }

            $this->info('The restored database matches the expected recovery checksum.');
        }

        if (! $manifest['valid']) {
            $this->error('Recovery verification found missing or invalid critical state.');

            return self::FAILURE;
        }

        $this->info('Recovery verification passed.');

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $actual */
    private function compare(string $path, array $actual): bool
    {
        try {
            $baseline = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('The baseline manifest could not be read.');

            return false;
        }

        if (! is_array($baseline) || ! is_array($baseline['checks'] ?? null)) {
            $this->error('The baseline manifest is invalid.');

            return false;
        }

        if ($baseline['checks'] !== $actual['checks']) {
            $this->error('The restored database does not match the baseline recovery checks.');

            return false;
        }

        $this->info('The restored database matches the baseline recovery checks.');

        return true;
    }
}
