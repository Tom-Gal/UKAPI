<?php

namespace App\Domain\Mot;

use InvalidArgumentException;

final readonly class MotTestData
{
    /** @param list<MotDefectData> $defects */
    public function __construct(
        public ?string $completedDate,
        public ?string $testNumber,
        public ?string $dataSource,
        public ?string $expiryDate,
        public ?string $registrationAtTimeOfTest,
        public ?string $testResult,
        public ?string $odometerValue,
        public ?string $odometerUnit,
        public ?string $odometerResultType,
        public ?string $location,
        public array $defects,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $completedDate = self::string($payload['completedDate'] ?? null);
        $testResult = self::string($payload['testResult'] ?? null);
        $odometerResultType = self::string($payload['odometerResultType'] ?? null);
        $dataSource = self::string($payload['dataSource'] ?? null);
        if (! in_array($testResult, ['PASSED', 'FAILED'], true)
            || ! in_array($odometerResultType, ['READ', 'UNREADABLE', 'NO_ODOMETER'], true)
            || ! in_array($dataSource, ['DVSA', 'DVA NI', 'CVS'], true)) {
            throw new InvalidArgumentException('MOT test response is missing required fields.');
        }

        $defects = $payload['defects'] ?? [];
        if (! is_array($defects) || ! array_is_list($defects)) {
            throw new InvalidArgumentException('MOT test defects are invalid.');
        }

        $mappedDefects = [];
        foreach ($defects as $defect) {
            if (! is_array($defect)) {
                throw new InvalidArgumentException('MOT test defect is not an object.');
            }
            $mappedDefects[] = MotDefectData::fromArray($defect);
        }

        return new self(
            completedDate: $completedDate,
            testNumber: self::scalarString($payload['motTestNumber'] ?? null),
            dataSource: $dataSource,
            expiryDate: self::string($payload['expiryDate'] ?? null),
            registrationAtTimeOfTest: self::string(
                $payload['registrationAtTimeOfTest'] ?? $payload['regMarkTimeOfTest'] ?? null,
            ),
            testResult: $testResult,
            odometerValue: self::scalarString($payload['odometerValue'] ?? null),
            odometerUnit: self::string($payload['odometerUnit'] ?? null),
            odometerResultType: $odometerResultType,
            location: self::string($payload['location'] ?? null),
            defects: $mappedDefects,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'completedDate' => $this->completedDate,
            'motTestNumber' => $this->testNumber,
            'dataSource' => $this->dataSource,
            'expiryDate' => $this->expiryDate,
            'registrationAtTimeOfTest' => $this->registrationAtTimeOfTest,
            'testResult' => $this->testResult,
            'odometerValue' => $this->odometerValue,
            'odometerUnit' => $this->odometerUnit,
            'odometerResultType' => $this->odometerResultType,
            'location' => $this->location,
            'defects' => array_map(
                static fn (MotDefectData $defect): array => $defect->toArray(),
                $this->defects,
            ),
        ];
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function scalarString(mixed $value): ?string
    {
        return is_string($value) || is_int($value) ? (string) $value : null;
    }
}
