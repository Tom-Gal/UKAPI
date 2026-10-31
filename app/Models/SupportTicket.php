<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string|null $request_id
 * @property string $title
 * @property string $notes
 * @property string $status
 * @property int|null $opened_by_user_id
 * @property int|null $closed_by_user_id
 * @property CarbonImmutable|null $closed_at
 * @property string|null $resolution
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property User|null $openedBy
 * @property User|null $closedBy
 */
final class SupportTicket extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'closed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }
}
