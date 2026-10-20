<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasetSnapshotHead extends Model
{
    protected $fillable = [
        'dataset',
        'scope_key',
        'snapshot_id',
        'switched_at',
    ];

    protected function casts(): array
    {
        return ['switched_at' => 'datetime'];
    }

    /** @return BelongsTo<DatasetSnapshot, $this> */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(DatasetSnapshot::class, 'snapshot_id');
    }
}
