<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\InteractsWithPagination;
use Illuminate\Foundation\Http\FormRequest;

class SicSearchRequest extends FormRequest
{
    use InteractsWithPagination;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $query = $this->query('q');
        if (is_string($query)) {
            $this->merge(['q' => preg_replace('/\s+/', ' ', trim($query))]);
        }
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:200'],
            ...$this->paginationRules(),
        ];
    }

    public function queryText(): string
    {
        return (string) $this->validated('q');
    }
}
