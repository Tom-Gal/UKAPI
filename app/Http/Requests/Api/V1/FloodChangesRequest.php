<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class FloodChangesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'since' => ['required', 'string', 'max:26'],
            'until' => ['nullable', 'string', 'max:26'],
        ];
    }

    public function since(): string
    {
        return (string) $this->validated('since');
    }

    public function until(): ?string
    {
        $until = $this->validated('until');

        return is_string($until) && $until !== '' ? $until : null;
    }
}
