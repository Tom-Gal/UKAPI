<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class NearbyPostcodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'limit' => ['sometimes', 'integer', 'between:1,100'],
            'radius' => ['sometimes', 'integer', 'between:1,2000'],
        ];
    }

    public function limit(): int
    {
        return (int) ($this->validated('limit') ?? 10);
    }

    public function radiusMetres(): int
    {
        return (int) ($this->validated('radius') ?? 100);
    }
}
