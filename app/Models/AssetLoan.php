<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetLoan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'asset_id',
        'borrowed_by',
        'borrowed_by_contact',
        'relationship',
        'loaned_at',
        'due_back_at',
        'returned_at',
        'condition_at_loan',
        'condition_at_return',
        'status',
        'notes',
    ];

    protected $casts = [
        'loaned_at' => 'date',
        'due_back_at' => 'date',
        'returned_at' => 'date',
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

    public function isOverdue(): bool
    {
        return $this->status === 'active' && now()->isAfter($this->due_back_at);
    }

    public function daysOverdue(): int
    {
        if ($this->isOverdue()) {
            return now()->diffInDays($this->due_back_at, false);
        }
        return 0;
    }

    public function returnAsset(string $conditionAtReturn, ?string $notes = null): self
    {
        $this->update([
            'status' => 'returned',
            'returned_at' => now(),
            'condition_at_return' => $conditionAtReturn,
            'notes' => $notes,
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
            'returned_at' => now(),
        ]);

        return $this;
    }
}
