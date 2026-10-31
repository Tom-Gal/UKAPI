<?php

namespace App\Domain\Geography;

use App\Support\Api\ApiException;
use InvalidArgumentException;

final class CouncilReference
{
    /** @var array<string, CouncilData>|null */
    private ?array $records = null;

    public function find(UkCouncilCode $code): ?CouncilData
    {
        $records = $this->records();

        return $records[$code->value()] ?? null;
    }

    /** @return array<string, CouncilData> */
    private function records(): array
    {
        if ($this->records !== null) {
            return $this->records;
        }

        $path = resource_path('reference/uk-councils-2024.json');
        $contents = is_file($path) ? file_get_contents($path) : false;

        if (! is_string($contents)) {
            throw ApiException::councilReferenceUnavailable();
        }

        try {
            $snapshot = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ApiException::councilReferenceUnavailable();
        }

        if (! is_array($snapshot) || ! is_string($snapshot['version'] ?? null) || ! is_array($snapshot['records'] ?? null)) {
            throw ApiException::councilReferenceUnavailable();
        }

        $records = [];
        try {
            foreach ($snapshot['records'] as $record) {
                if (! is_array($record)) {
                    throw new InvalidArgumentException('Council snapshot record is not an object.');
                }

                $record['reference_version'] = $snapshot['version'];
                $council = CouncilData::fromArray($record);
                $records[$council->code] = $council;
            }
        } catch (InvalidArgumentException) {
            throw ApiException::councilReferenceUnavailable();
        }

        return $this->records = $records;
    }
}
