<?php

namespace App\Domain\Mot;

use InvalidArgumentException;

final readonly class MotDefectData
{
    public function __construct(
        public ?string $text,
        public ?string $type,
        public ?bool $dangerous,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $dangerous = $payload['dangerous'] ?? null;
        if ($dangerous !== null && ! is_bool($dangerous)) {
            throw new InvalidArgumentException('MOT defect dangerous flag is invalid.');
        }

        return new self(
            text: self::string($payload['text'] ?? null),
            type: self::string($payload['type'] ?? null),
            dangerous: $dangerous,
        );
    }

    /** @return array{text: ?string, type: ?string, dangerous: ?bool} */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'type' => $this->type,
            'dangerous' => $this->dangerous,
        ];
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
