<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class VatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['bail', 'required', 'string', 'regex:/^(?:0|[1-9]\\d{0,11})(?:\\.\\d{1,2})?$/'],
            'rate' => ['bail', 'required', 'string', 'regex:/^(?:0|[1-9]\\d{0,2})(?:\\.\\d{1,2})?$/', 'numeric', 'between:0,100'],
        ];
    }

    public function amount(): string
    {
        return (string) $this->validated('amount');
    }

    public function rate(): string
    {
        return (string) $this->validated('rate');
    }
}
