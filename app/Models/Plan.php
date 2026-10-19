<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'key',
        'label',
        'features',
        'monthly_request_quota',
        'burst_requests_per_second',
        'max_page_size',
        'commercial_use',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'commercial_use' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /** @return HasMany<PlanPrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }
}
