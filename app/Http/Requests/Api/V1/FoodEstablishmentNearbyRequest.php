<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Geography\UkPostcode;
use App\Http\Requests\Api\V1\Concerns\InteractsWithPagination;
use Illuminate\Foundation\Http\FormRequest;

final class FoodEstablishmentNearbyRequest extends FormRequest
{
    use InteractsWithPagination;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'postcode' => ['bail', 'required', 'string', 'max:16'],
            'radius' => ['bail', 'required', 'integer', 'between:1,25'],
            'scheme' => ['sometimes', 'nullable', 'string', 'in:FHRS,FHIS'],
            'rating' => ['sometimes', 'nullable', 'string', 'in:0,1,2,3,4,5,Pass,ImprovementRequired,AwaitingPublication,AwaitingInspection,Exempt'],
            'rating_operator' => ['sometimes', 'nullable', 'string', 'in:Equal,GreaterThanOrEqual,LessThanOrEqual'],
            ...$this->paginationRules(),
        ];
    }

    public function postcode(): UkPostcode
    {
        return UkPostcode::from((string) $this->validated('postcode'));
    }

    public function radiusKilometres(): int
    {
        return (int) $this->validated('radius');
    }

    public function scheme(): ?string
    {
        return $this->optionalString('scheme');
    }

    public function rating(): ?string
    {
        return $this->optionalString('rating');
    }

    public function ratingOperator(): ?string
    {
        return $this->optionalString('rating_operator');
    }

    private function optionalString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
