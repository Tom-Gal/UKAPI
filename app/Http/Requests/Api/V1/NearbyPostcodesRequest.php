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

    /**
     * Query parameters for Scribe.
     *
     * @return array<string, array<string, int|string>>
     */
    public function queryParameters(): array
    {
        return [
            'limit' => [
                'description' => 'Number of results to return, from 1 to 100. Defaults to 10.',
                'example' => 5,
            ],
            'radius' => [
                'description' => 'Search radius in metres, from 1 to 2,000. Defaults to 100.',
                'example' => 500,
            ],
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
