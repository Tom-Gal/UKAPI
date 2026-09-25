<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Crime\YearMonth;
use App\Domain\Geography\UkPostcode;
use Illuminate\Foundation\Http\FormRequest;

final class CrimePostcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'postcode' => ['required', 'string', 'max:16'],
            'month' => ['nullable', 'date_format:Y-m'],
        ];
    }

    public function postcode(): UkPostcode
    {
        return UkPostcode::from((string) $this->validated('postcode'));
    }

    public function month(): ?YearMonth
    {
        $month = $this->validated('month');

        return YearMonth::from(is_string($month) ? $month : null);
    }
}
