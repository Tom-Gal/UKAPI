<?php

namespace App\Domain\Companies;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class FilingData
{
    public function __construct(
        public ?string $transactionId,
        public ?string $category,
        public ?string $type,
        public string $filedOn,
        public ?string $description,
        public ?int $pages,
    ) {}

    /** @param array<string, mixed> $filing */
    public static function fromCompaniesHouse(array $filing): self
    {
        return new self(
            transactionId: self::string($filing['transaction_id'] ?? null),
            category: self::string($filing['category'] ?? null),
            type: self::string($filing['type'] ?? null),
            filedOn: self::requiredDate($filing['date'] ?? null),
            description: self::string($filing['description'] ?? null),
            pages: self::integer($filing['pages'] ?? null),
        );
    }

    /** @param array<string, mixed> $filing */
    public static function fromArray(array $filing): self
    {
        return new self(
            transactionId: self::string($filing['transaction_id'] ?? null),
            category: self::string($filing['category'] ?? null),
            type: self::string($filing['type'] ?? null),
            filedOn: self::requiredDate($filing['filed_on'] ?? null),
            description: self::string($filing['description'] ?? null),
            pages: self::integer($filing['pages'] ?? null),
        );
    }

    /** @return array{transaction_id: ?string, category: ?string, type: ?string, filed_on: string, description: ?string, pages: ?int} */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'category' => $this->category,
            'type' => $this->type,
            'filed_on' => $this->filedOn,
            'description' => $this->description,
            'pages' => $this->pages,
        ];
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private static function requiredDate(mixed $value): string
    {
        $date = self::string($value);
        if ($date === null) {
            throw new InvalidArgumentException('Companies House filing history response is missing a date.');
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Companies House returned an invalid filing date.');
        }

        return $date;
    }

    private static function integer(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if ((! is_int($value) && ! is_string($value)) || filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('Companies House returned an invalid filing page count.');
        }

        $integer = (int) $value;
        if ($integer < 0) {
            throw new InvalidArgumentException('Companies House returned an invalid filing page count.');
        }

        return $integer;
    }
}
