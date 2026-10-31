<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class WorkingDaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'from' => ['bail', 'required', 'string', 'date_format:Y-m-d'],
            'to' => ['bail', 'required', 'string', 'date_format:Y-m-d', 'after_or_equal:from'],
            'division' => ['bail', 'required', 'string', 'in:england-and-wales,scotland,northern-ireland'],
        ];
    }

    public function from(): string
    {
        return (string) $this->validated('from');
    }

    public function to(): string
    {
        return (string) $this->validated('to');
    }

    public function division(): string
    {
        return (string) $this->validated('division');
    }
}
