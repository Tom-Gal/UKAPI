<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Geography\UkPostcode;
use Illuminate\Foundation\Http\FormRequest;

final class FloodPostcodeRequest extends FormRequest
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
        ];
    }

    public function postcode(): UkPostcode
    {
        return UkPostcode::from((string) $this->validated('postcode'));
    }
}
