<?php

namespace App\Domain\Mot;

use InvalidArgumentException;

final readonly class MotVehicleData
{
    /** @param list<MotTestData> $tests */
    public function __construct(
        public ?string $registration,
        public ?string $make,
        public ?string $model,
        public ?string $firstUsedDate,
        public ?string $registrationDate,
        public ?string $manufactureDate,
        public ?string $manufactureYear,
        public ?string $primaryColour,
        public ?string $engineSize,
        public ?string $fuelType,
        public string $hasOutstandingRecall,
        public ?string $motTestDueDate,
        public array $tests,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $registration = self::string($payload['registration'] ?? null);
        $recall = self::string($payload['hasOutstandingRecall'] ?? null);
        $tests = $payload['motTests'] ?? [];
        if (! in_array($recall, ['Yes', 'No', 'Unknown', 'Unavailable'], true)
            || ! is_array($tests)
            || ! array_is_list($tests)) {
            throw new InvalidArgumentException('MOT vehicle response is missing required fields.');
        }

        $mappedTests = [];
        foreach ($tests as $test) {
            if (! is_array($test)) {
                throw new InvalidArgumentException('MOT test is not an object.');
            }
            $mappedTests[] = MotTestData::fromArray($test);
        }

        return new self(
            registration: $registration,
            make: self::string($payload['make'] ?? null),
            model: self::string($payload['model'] ?? null),
            firstUsedDate: self::string($payload['firstUsedDate'] ?? null),
            registrationDate: self::string($payload['registrationDate'] ?? null),
            manufactureDate: self::string($payload['manufactureDate'] ?? null),
            manufactureYear: self::scalarString($payload['manufactureYear'] ?? null),
            primaryColour: self::string($payload['primaryColour'] ?? null),
            engineSize: self::scalarString($payload['engineSize'] ?? null),
            fuelType: self::string($payload['fuelType'] ?? null),
            hasOutstandingRecall: $recall,
            motTestDueDate: self::string($payload['motTestDueDate'] ?? null),
            tests: $mappedTests,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'registration' => $this->registration,
            'make' => $this->make,
            'model' => $this->model,
            'firstUsedDate' => $this->firstUsedDate,
            'registrationDate' => $this->registrationDate,
            'manufactureDate' => $this->manufactureDate,
            'manufactureYear' => $this->manufactureYear,
            'primaryColour' => $this->primaryColour,
            'engineSize' => $this->engineSize,
            'fuelType' => $this->fuelType,
            'hasOutstandingRecall' => $this->hasOutstandingRecall,
            'motTestDueDate' => $this->motTestDueDate,
            'motTests' => array_map(
                static fn (MotTestData $test): array => $test->toArray(),
                $this->tests,
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
