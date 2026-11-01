<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Food\FoodEstablishmentSearch;
use App\Domain\Geography\UkPostcode;
use App\Http\Requests\Api\V1\Concerns\InteractsWithPagination;
use Illuminate\Foundation\Http\FormRequest;

final class FoodEstablishmentSearchRequest extends FormRequest
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
            'name' => ['bail', 'nullable', 'string', 'min:2', 'max:200', 'not_regex:/^\s*$/', 'required_without:postcode'],
            'postcode' => ['bail', 'nullable', 'string', 'max:16', 'required_without:name'],
            'scheme' => ['sometimes', 'nullable', 'string', 'in:FHRS,FHIS'],
            'rating' => ['sometimes', 'nullable', 'string', 'in:0,1,2,3,4,5,Pass,ImprovementRequired,AwaitingPublication,AwaitingInspection,Exempt'],
            'rating_operator' => ['sometimes', 'nullable', 'string', 'in:Equal,GreaterThanOrEqual,LessThanOrEqual'],
            ...$this->paginationRules(),
        ];
    }

    public function search(): FoodEstablishmentSearch
    {
        $postcode = $this->validated('postcode');

        return FoodEstablishmentSearch::search(
            name: is_string($this->validated('name')) ? $this->validated('name') : null,
            postcode: is_string($postcode) && $postcode !== '' ? UkPostcode::from($postcode) : null,
            schemeType: $this->optionalString('scheme'),
            rating: $this->optionalString('rating'),
            ratingOperator: $this->optionalString('rating_operator'),
            page: $this->pageNumber(),
            perPage: $this->perPage(),
        );
    }

    private function optionalString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
