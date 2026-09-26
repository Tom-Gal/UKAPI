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

    /**
     * Query parameters for Scribe.
     *
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'amount' => [
                'description' => 'Amount in GBP, with up to two decimal places. Use the net amount for calculate and the gross amount for remove.',
                'example' => '100.00',
            ],
            'rate' => [
                'description' => 'VAT rate as a percentage, from 0 to 100, with up to two decimal places.',
                'example' => '20.00',
            ],
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
