<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\InteractsWithPagination;
use Illuminate\Foundation\Http\FormRequest;

class CompanyCollectionRequest extends FormRequest
{
    use InteractsWithPagination;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return $this->paginationRules();
    }
}
