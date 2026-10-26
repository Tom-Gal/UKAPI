<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $public_id
 * @property string $secret_hash
 * @property string $name
 * @property string $environment
 * @property string $status
 * @property list<string>|null $scopes
 * @property list<string>|null $ip_allow_list
 * @property string|null $last_used_endpoint_family
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property int|null $rotated_from_id
 * @property Carbon|null $revoked_at
 * @property Carbon|null $last_rotation_reminded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApiKey extends Model
{
    protected $fillable = [
        'public_id',
        'secret_hash',
        'name',
        'environment',
        'status',
        'scopes',
        'ip_allow_list',
        'last_used_endpoint_family',
        'last_used_at',
        'expires_at',
        'rotated_from_id',
        'revoked_at',
        'last_rotation_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'ip_allow_list' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_rotation_reminded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ApiKeyAuditEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(ApiKeyAuditEvent::class);
    }
}
