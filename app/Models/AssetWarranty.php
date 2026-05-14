<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetWarranty extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'asset_id',
        'warranty_type',
        'warranty_start_date',
        'warranty_end_date',
        'coverage_details',
        'provider_name',
        'provider_contact',
        'claim_process_url',
        'has_been_claimed',
        'claim_notes',
    ];

    protected $casts = [
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'has_been_claimed' => 'boolean',
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

    public function isActive(): bool
    {
        return now()->isBetween($this->warranty_start_date, $this->warranty_end_date);
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->warranty_end_date);
    }

    public function daysUntilExpiration(): int
    {
        return now()->diffInDays($this->warranty_end_date, false);
    }

    public function claimWarranty(string $notes): self
    {
        $this->update([
            'has_been_claimed' => true,
            'claim_notes' => $notes,
        ]);

        return $this;
    }
}
