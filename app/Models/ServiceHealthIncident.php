<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $service
 * @property string $failure_reason
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable $last_observed_at
 * @property CarbonImmutable|null $recovered_at
 * @property int $occurrences
 * @property int|null $latest_latency_ms
 * @property CarbonImmutable|null $acknowledged_at
 * @property string|null $acknowledgement_note
 * @property User|null $acknowledgedBy
 */
final class ServiceHealthIncident extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'last_observed_at' => 'immutable_datetime',
            'recovered_at' => 'immutable_datetime',
            'acknowledged_at' => 'immutable_datetime',
            'occurrences' => 'integer',
            'latest_latency_ms' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
