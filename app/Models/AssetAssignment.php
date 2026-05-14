<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetAssignment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'asset_id',
        'organization_id',
        'assigned_to',
        'assigned_by',
        'quantity',
        'status',
        'assignment_notes',
        'assigned_at',
        'received_at',
        'returned_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'assigned_at' => 'datetime',
        'received_at' => 'datetime',
        'returned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function conditionReports(): HasMany
    {
        return $this->hasMany(AssetConditionReport::class, 'asset_assignment_id');
    }

    public function confirmReceipt(): self
    {
        $this->update([
            'status' => 'in_use',
            'received_at' => now(),
        ]);

        return $this;
    }

    public function markAsReturned(): self
    {
        $this->update([
            'status' => 'returned',
            'returned_at' => now(),
        ]);

        return $this;
    }

    public function markAsLost(): self
    {
        $this->update([
            'status' => 'lost',
            'returned_at' => now(),
        ]);

        return $this;
    }

    public function markAsDamaged(): self
    {
        $this->update([
            'status' => 'damaged',
        ]);

        return $this;
    }
}
