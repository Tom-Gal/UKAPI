<?php

namespace App\Console\Commands;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

final class RefreshSicReference extends Command
{
    protected $signature = 'ukapi:sic:refresh';

    protected $description = 'Refresh the local Companies House condensed SIC 2007 reference snapshot.';

    public function handle(): int
    {
        $sourceUrl = config('ukapi.sic_reference.source_url');
        $snapshotPath = config('ukapi.sic_reference.snapshot_path');

        if (! is_string($sourceUrl) || $sourceUrl === '' || ! is_string($snapshotPath) || $snapshotPath === '') {
            $this->error('The SIC source URL or snapshot path is not configured.');

            return self::FAILURE;
        }

        try {
            $request = Http::accept('text/html,application/xhtml+xml')
                ->connectTimeout((int) config('ukapi.companies_house.connect_timeout_seconds'))
                ->timeout((int) config('ukapi.companies_house.timeout_seconds'));
            $caBundle = config('ukapi.http_ca_bundle');

            if (is_string($caBundle) && $caBundle !== '') {
                $request = $request->withOptions(['verify' => $caBundle]);
            }

            $response = $request->get($sourceUrl);
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('The Companies House SIC source could not be reached.');

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error(sprintf('The Companies House SIC source returned HTTP %d.', $response->status()));

            return self::FAILURE;
        }

        $records = $this->recordsFromHtml($response->body());
        if ($records === []) {
            $this->error('The Companies House SIC source did not contain any valid code records.');

            return self::FAILURE;
        }

        try {
            $snapshot = [
                'version' => 'companies_house_condensed_sic_2007',
                'retrieved_at' => now()->toIso8601String(),
                'checksum' => hash('sha256', json_encode($records, JSON_THROW_ON_ERROR)),
                'records' => $records,
            ];

            File::ensureDirectoryExists(dirname($snapshotPath));
            File::replace(
                $snapshotPath,
                json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL,
            );
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('The SIC reference snapshot could not be written.');

            return self::FAILURE;
        }

        $this->info(sprintf('Refreshed %d Companies House condensed SIC 2007 records.', count($records)));

        return self::SUCCESS;
    }

    /** @return list<array{code: string, description: string}> */
    private function recordsFromHtml(string $html): array
    {
        $previousUseErrors = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseErrors);

        $rows = (new DOMXPath($document))->query('//table[@id="sic-codes"]//tr');
        if ($rows === false) {
            return [];
        }

        $records = [];
        foreach ($rows as $row) {
            if (! $row instanceof DOMElement) {
                continue;
            }

            $cells = $row->getElementsByTagName('td');
            if ($cells->count() < 2) {
                continue;
            }

            $code = preg_replace('/\s+/', '', trim($cells->item(0)->textContent));
            $description = preg_replace('/\s+/', ' ', trim($cells->item(1)->textContent));
            if (! is_string($code) || ! preg_match('/^\d{5}$/', $code) || ! is_string($description) || $description === '') {
                continue;
            }

            $records[$code] = ['code' => $code, 'description' => $description];
        }

        ksort($records, SORT_STRING);

        return array_values($records);
    }
}
