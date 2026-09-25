<?php

namespace App\Domain\Flood;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class FloodWarning
{
    /**
     * @param  array{code: ?string, county: ?string, river_or_sea: ?string, name: ?string}  $area
     */
    public function __construct(
        public string $id,
        public ?int $severity,
        public ?string $severityLevel,
        public ?string $type,
        public ?string $message,
        public ?string $description,
        public ?string $raisedAt,
        public ?string $severityChangedAt,
        public ?string $messageChangedAt,
        public array $area,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromEnvironmentAgency(array $payload): self
    {
        $id = self::string($payload['floodAreaID'] ?? null) ?? self::lastPathSegment($payload['@id'] ?? null);

        if ($id === null) {
            throw new InvalidArgumentException('Environment Agency flood warning has no identifier.');
        }

        $area = is_array($payload['floodArea'] ?? null) ? $payload['floodArea'] : [];

        return new self(
            id: $id,
            severity: self::integer($payload['severity'] ?? null),
            severityLevel: self::string($payload['severityLevel'] ?? null),
            type: self::string($payload['type'] ?? null),
            message: self::string($payload['message'] ?? null),
            description: self::string($payload['description'] ?? null),
            raisedAt: self::timestamp($payload['timeRaised'] ?? null),
            severityChangedAt: self::timestamp($payload['timeSeverityChanged'] ?? null),
            messageChangedAt: self::timestamp($payload['timeMessageChanged'] ?? null),
            area: [
                'code' => self::string($area['notation'] ?? null),
                'county' => self::string($area['county'] ?? null),
                'river_or_sea' => self::string($area['riverOrSea'] ?? null),
                'name' => self::string($area['label'] ?? null),
            ],
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $id = self::string($data['id'] ?? null);

        if ($id === null) {
            throw new InvalidArgumentException('Cached flood warning has no identifier.');
        }

        $area = is_array($data['area'] ?? null) ? $data['area'] : [];

        return new self(
            id: $id,
            severity: self::integer($data['severity'] ?? null),
            severityLevel: self::string($data['severity_level'] ?? null),
            type: self::string($data['type'] ?? null),
            message: self::string($data['message'] ?? null),
            description: self::string($data['description'] ?? null),
            raisedAt: self::timestamp($data['raised_at'] ?? null),
            severityChangedAt: self::timestamp($data['severity_changed_at'] ?? null),
            messageChangedAt: self::timestamp($data['message_changed_at'] ?? null),
            area: [
                'code' => self::string($area['code'] ?? null),
                'county' => self::string($area['county'] ?? null),
                'river_or_sea' => self::string($area['river_or_sea'] ?? null),
                'name' => self::string($area['name'] ?? null),
            ],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'severity' => $this->severity,
            'severity_level' => $this->severityLevel,
            'type' => $this->type,
            'message' => $this->message,
            'description' => $this->description,
            'raised_at' => $this->raisedAt,
            'severity_changed_at' => $this->severityChangedAt,
            'message_changed_at' => $this->messageChangedAt,
            'area' => $this->area,
        ];
    }

    public function sourceUpdatedAt(): ?string
    {
        $timestamps = array_filter([
            $this->raisedAt,
            $this->severityChangedAt,
            $this->messageChangedAt,
        ]);

        return $timestamps === [] ? null : max($timestamps);
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function timestamp(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toIso8601String();
        } catch (\Exception) {
            return null;
        }
    }

    private static function lastPathSegment(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $segment = basename(parse_url($value, PHP_URL_PATH) ?: '');

        return $segment !== '' ? $segment : null;
    }
}
