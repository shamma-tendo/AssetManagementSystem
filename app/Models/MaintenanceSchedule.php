<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceSchedule extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'asset_id',
        'service_type',
        'last_service_date',
        'next_service_date',
        'service_interval_days',
        'service_provider',
        'estimated_cost',
        'notes',
        'is_reminder_sent',
    ];

    protected $casts = [
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'service_interval_days' => 'integer',
        'estimated_cost' => 'decimal:2',
        'is_reminder_sent' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function isDue(): bool
    {
        return now()->isAfter($this->next_service_date);
    }

    public function daysUntilDue(): int
    {
        return now()->diffInDays($this->next_service_date, false);
    }

    public function markAsServiced(string $provider = null): self
    {
        $this->update([
            'last_service_date' => now()->toDateString(),
            'next_service_date' => $this->service_interval_days 
                ? now()->addDays($this->service_interval_days)->toDateString()
                : $this->next_service_date,
            'service_provider' => $provider,
            'is_reminder_sent' => false,
        ]);

        return $this;
    }
}
