<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $request_id
 * @property string $method
 * @property string|null $route_path
 * @property string|null $route_name
 * @property string $endpoint
 * @property int $status
 * @property int $duration_ms
 * @property string|null $error_code
 * @property string|null $error_reason
 * @property string|null $error_message
 * @property int|null $user_id
 * @property int|null $api_key_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property User|null $user
 * @property ApiKey|null $apiKey
 */
final class ApiRequestLog extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'duration_ms' => 'integer',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ApiKey, $this> */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }
}
