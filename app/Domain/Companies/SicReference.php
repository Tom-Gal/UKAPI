<?php

namespace App\Domain\Companies;

use App\Support\Api\ApiException;
use InvalidArgumentException;

final class SicReference
{
    public function snapshot(): SicSnapshot
    {
        $path = $this->snapshotPath();
        if ($path === null) {
            throw ApiException::sicReferenceUnavailable();
        }

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

    private function snapshotPath(): ?string
    {
        $snapshotPath = config('ukapi.sic_reference.snapshot_path');
        if (is_string($snapshotPath) && $snapshotPath !== '' && is_file($snapshotPath)) {
            return $snapshotPath;
        }

        $seedSnapshotPath = config('ukapi.sic_reference.seed_snapshot_path');

        return is_string($seedSnapshotPath) && $seedSnapshotPath !== '' && is_file($seedSnapshotPath)
            ? $seedSnapshotPath
            : null;
    }
}
