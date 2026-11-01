<?php

namespace App\Domain\Food;

use App\Support\Api\ApiException;

final readonly class FoodEstablishmentId
{
    private function __construct(private int $value) {}

    public static function from(string $value): self
    {
        $normalised = trim($value);

        if (! preg_match('/^[1-9]\d{0,9}$/', $normalised)) {
            throw ApiException::invalidFoodEstablishmentId();
        }

        return new self((int) $normalised);
    }

    public function value(): int
    {
        return $this->value;
    }
}
