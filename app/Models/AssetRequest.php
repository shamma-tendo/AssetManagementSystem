<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetRequest extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'requested_by',
        'approved_by',
        'title',
        'description',
        'quantity',
        'asset_type',
        'estimated_cost',
        'status',
        'approval_notes',
        'requested_at',
        'reviewed_at',
        'fulfilled_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'estimated_cost' => 'decimal:2',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function approve(User $approver, ?string $notes = null): self
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'reviewed_at' => now(),
            'approval_notes' => $notes,
        ]);

        return $this;
    }

    public function reject(User $approver, string $notes): self
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'reviewed_at' => now(),
            'approval_notes' => $notes,
        ]);

        return $this;
    }
}
