<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CompanyCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
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
            'page' => [
                'description' => 'One-based result page. Defaults to 1.',
                'example' => 1,
            ],
            'per_page' => [
                'description' => 'Results to return per page, from 1 to 100. Defaults to 25.',
                'example' => 25,
            ],
        ];
    }

    public function pageNumber(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 25);
    }
}
