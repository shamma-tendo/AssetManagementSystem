<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotReading extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'asset_id', 'sensor_id', 'sensor_type', 'metric_name',
        'metric_value', 'unit', 'reading_timestamp', 'metadata'
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'metric_value' => 'decimal:4',
        'reading_timestamp' => 'datetime',
        'metadata' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
