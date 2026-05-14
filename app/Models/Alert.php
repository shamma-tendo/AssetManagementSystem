<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'asset_id',
        'alert_type',
        'title',
        'message',
        'severity',
        'is_resolved',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function resolve(string $notes = null): self
    {
        $this->update([
            'is_resolved' => true,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);

        return $this;
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function isUrgent(): bool
    {
        return in_array($this->severity, ['high', 'critical']);
    }
}
