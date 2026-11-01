<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\UkPostcode;
use App\Domain\Planning\PlanningConstraintQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class PlanningConstraintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'postcode' => ['bail', 'nullable', 'string', 'max:16', 'required_without_all:lat,lng'],
            'lat' => ['bail', 'nullable', 'numeric', 'between:-90,90', 'required_without:postcode', 'required_with:lng'],
            'lng' => ['bail', 'nullable', 'numeric', 'between:-180,180', 'required_without:postcode', 'required_with:lat'],
            'limit' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('postcode') && ($this->filled('lat') || $this->filled('lng'))) {
                $validator->errors()->add('postcode', 'Use postcode or coordinates, not both.');
            }
        });
    }

    public function constraints(): PlanningConstraintQuery
    {
        $limit = (int) ($this->validated('limit') ?? 50);
        $postcode = $this->validated('postcode');

        if (is_string($postcode) && $postcode !== '') {
            return PlanningConstraintQuery::fromPostcode(UkPostcode::from($postcode), $limit);
        }

        $coordinates = Coordinates::from(
            (string) $this->validated('lat'),
            (string) $this->validated('lng'),
        );

        return PlanningConstraintQuery::fromCoordinates($coordinates->latitude, $coordinates->longitude, $limit);
    }
}
