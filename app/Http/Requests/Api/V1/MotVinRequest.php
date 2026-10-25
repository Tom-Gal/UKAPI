<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class MotVinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['vin' => $this->route('vin')]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['vin' => ['required', 'string', 'size:17', 'alpha_num', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/i']];
    }

    public function vin(): string
    {
        return strtoupper(trim((string) $this->route('vin')));
    }
}
