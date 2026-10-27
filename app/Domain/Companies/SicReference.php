<?php

namespace App\Domain\Companies;

use App\Domain\Snapshots\SnapshotStore;
use App\Support\Api\ApiException;
use InvalidArgumentException;

final class SicReference
{
    public function __construct(private readonly SnapshotStore $snapshots) {}

    public function snapshot(): SicSnapshot
    {
        $snapshotPath = config('ukapi.sic_reference.snapshot_path');
        if (is_string($snapshotPath) && $snapshotPath !== '' && is_file($snapshotPath)) {
            return $this->snapshotFromPath($snapshotPath);
        }

        $durable = $this->snapshots->active('companies_house.sic_2007', 'reference');
        if ($durable !== null) {
            try {
                return SicSnapshot::fromArray([
                    'version' => 'companies_house_condensed_sic_2007',
                    'retrieved_at' => ($durable->source_updated_at ?? $durable->captured_at)?->toIso8601String(),
                    'checksum' => $durable->checksum,
                    'records' => $this->snapshots->payload($durable),
                ]);
            } catch (InvalidArgumentException) {
                throw ApiException::sicReferenceUnavailable();
            }
        }

        $seedSnapshotPath = config('ukapi.sic_reference.seed_snapshot_path');
        if (! is_string($seedSnapshotPath) || $seedSnapshotPath === '' || ! is_file($seedSnapshotPath)) {
            throw ApiException::sicReferenceUnavailable();
        }

        return $this->snapshotFromPath($seedSnapshotPath);
    }

    private function snapshotFromPath(string $path): SicSnapshot
    {
        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw ApiException::sicReferenceUnavailable();
        }

        try {
            $payload = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($payload)) {
                throw new InvalidArgumentException('SIC reference snapshot is invalid.');
            }

            return SicSnapshot::fromArray($payload);
        } catch (\JsonException|InvalidArgumentException) {
            throw ApiException::sicReferenceUnavailable();
        }
    }
}
