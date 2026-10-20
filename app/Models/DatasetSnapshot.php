<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $dataset
 * @property string $scope_key
 * @property string $version
 * @property string $source
 * @property Carbon|null $captured_at
 * @property Carbon|null $source_updated_at
 * @property string $checksum
 * @property int $record_count
 * @property array<string, mixed> $payload
 */
class DatasetSnapshot extends Model
{
    protected $fillable = [
        'dataset',
        'scope_key',
        'version',
        'source',
        'captured_at',
        'source_updated_at',
        'checksum',
        'record_count',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'source_updated_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): void {
            throw new \LogicException('Dataset snapshots are immutable.');
        });
    }

    /** @return HasOne<DatasetSnapshotHead, $this> */
    public function head(): HasOne
    {
        return $this->hasOne(DatasetSnapshotHead::class, 'snapshot_id');
    }
}
