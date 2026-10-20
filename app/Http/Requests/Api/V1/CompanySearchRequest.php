<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\InteractsWithPagination;
use Illuminate\Foundation\Http\FormRequest;

class CompanySearchRequest extends FormRequest
{
    use InteractsWithPagination;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'q' => ['bail', 'required', 'string', 'min:2', 'max:200', 'not_regex:/^\\s*$/'],
            ...$this->paginationRules(),
        ];
    }

    public function queryText(): string
    {
        return (string) preg_replace('/\\s+/', ' ', trim((string) $this->validated('q')));
    }
}
