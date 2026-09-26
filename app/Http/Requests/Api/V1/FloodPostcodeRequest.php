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

    /**
     * Query parameters for Scribe.
     *
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'postcode' => [
                'description' => 'A UK postcode. It is normalised and resolved to coordinates before querying the Environment Agency.',
                'example' => 'BL2 6XX',
            ],
        ];
    }

    public function postcode(): UkPostcode
    {
        return UkPostcode::from((string) $this->validated('postcode'));
    }
}
