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

    /**
     * Query parameters for Scribe.
     *
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'postcode' => [
                'description' => 'A UK postcode. It is normalised and resolved to coordinates before the Police.uk request.',
                'example' => 'BL2 6XX',
            ],
            'month' => [
                'description' => 'A non-future month in YYYY-MM format. Omit it to use the latest provider data.',
                'example' => '2026-07',
            ],
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
