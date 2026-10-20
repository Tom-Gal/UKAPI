<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Crime\YearMonth;
use App\Domain\Geography\UkPostcode;
use Illuminate\Foundation\Http\FormRequest;

final class CrimeChangesRequest extends FormRequest
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
            'since' => ['required', 'date_format:Y-m'],
            'until' => ['nullable', 'date_format:Y-m'],
        ];
    }

    public function postcode(): UkPostcode
    {
        return UkPostcode::from((string) $this->validated('postcode'));
    }

    public function since(): YearMonth
    {
        $month = YearMonth::from((string) $this->validated('since'), 'since');

        assert($month instanceof YearMonth);

        return $month;
    }

    public function until(): ?YearMonth
    {
        $month = $this->validated('until');

        return YearMonth::from(is_string($month) ? $month : null, 'until');
    }
}
