<?php

namespace App\Domain\Companies;

use App\Support\Api\ApiException;

final class SicExport
{
    // This is deliberately limited to the small condensed SIC reference dataset.
    private const MAX_RECORDS = 2000;

    public const SOURCE_URL = 'https://resources.companieshouse.gov.uk/sic/';

    public const LICENCE_URL = 'https://www.nationalarchives.gov.uk/doc/open-government-licence/version/3/';

    public const ATTRIBUTION = 'Contains public sector information licensed under the Open Government Licence v3.0.';

    public function __construct(private readonly SicLookup $lookup) {}

    /** @return array{csv: string, version: string, checksum: string} */
    public function download(): array
    {
        $snapshot = $this->lookup->exportSnapshot();
        $payload = $snapshot->payload;
        if ($payload === [] || count($payload) > self::MAX_RECORDS) {
            throw ApiException::sicReferenceUnavailable();
        }

        $records = array_map(static fn (array $record): SicData => SicData::fromArray($record), $payload);
        usort($records, static fn (SicData $left, SicData $right): int => strcmp($left->code, $right->code));

        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            throw ApiException::sicReferenceUnavailable();
        }

        try {
            fputcsv($stream, [
                'code', 'description', 'snapshot_version', 'snapshot_checksum',
                'source', 'source_url', 'licence', 'licence_url', 'attribution',
            ], escape: '', eol: "\r\n");

            foreach ($records as $record) {
                $cells = [
                    $record->code, $record->description, $snapshot->version, $snapshot->checksum,
                    'Companies House condensed SIC 2007', self::SOURCE_URL,
                    'Open Government Licence v3.0', self::LICENCE_URL, self::ATTRIBUTION,
                ];
                fputcsv($stream, array_map($this->safeCell(...), $cells), escape: '', eol: "\r\n");
            }

            rewind($stream);
            $csv = stream_get_contents($stream);
            if ($csv === false) {
                throw ApiException::sicReferenceUnavailable();
            }

            return ['csv' => $csv, 'version' => $snapshot->version, 'checksum' => $snapshot->checksum];
        } finally {
            fclose($stream);
        }
    }

    private function safeCell(string $value): string
    {
        // Quote spreadsheet formulas, including those hidden after whitespace/control characters.
        return preg_match('/^(?:[\t\r\n]|[\x00-\x20]*[=+@-])/u', $value) === 1 ? "'".$value : $value;
    }
}
