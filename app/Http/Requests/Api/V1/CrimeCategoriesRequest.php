<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Crime\YearMonth;
use Illuminate\Foundation\Http\FormRequest;

final class CrimeCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'month' => ['nullable', 'date_format:Y-m'],
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
            'month' => [
                'description' => 'A non-future month in YYYY-MM format. Omit it to use the latest provider categories.',
                'example' => '2026-07',
            ],
        ];
    }

    public function month(): ?YearMonth
    {
        $month = $this->validated('month');

        return YearMonth::from(is_string($month) ? $month : null);
    }
}
