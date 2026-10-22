<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $service
 * @property string $status
 * @property int $consecutive_failures
 * @property CarbonImmutable|null $last_attempt_at
 * @property CarbonImmutable|null $last_checked_at
 * @property CarbonImmutable|null $last_success_at
 * @property CarbonImmutable|null $source_date
 * @property CarbonImmutable|null $reference_retrieved_at
 * @property CarbonImmutable|null $incident_notified_at
 * @property CarbonImmutable|null $last_alert_at
 */
class ServiceHealthCheck extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'last_attempt_at' => 'immutable_datetime',
            'last_checked_at' => 'immutable_datetime',
            'last_success_at' => 'immutable_datetime',
            'source_date' => 'immutable_datetime',
            'reference_retrieved_at' => 'immutable_datetime',
            'incident_notified_at' => 'immutable_datetime',
            'last_alert_at' => 'immutable_datetime',
            'consecutive_failures' => 'integer',
        ];
    }
}
