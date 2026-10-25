<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class MotRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['registration' => $this->route('registration')]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['registration' => ['required', 'string', 'regex:/^(?=.*[A-Z0-9])[A-Z0-9\s]{2,8}$/i']];
    }

    public function registration(): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', trim((string) $this->route('registration'))));
    }
}
