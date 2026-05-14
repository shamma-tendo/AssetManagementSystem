<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inspection extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'asset_id', 'inspection_type', 'compliance_standard', 'status',
        'scheduled_date', 'completed_date', 'next_due_date', 'inspector_id',
        'findings', 'corrective_actions', 'compliance_met', 'certification_number',
        'certification_expiry'
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'scheduled_date' => 'datetime',
        'completed_date' => 'datetime',
        'next_due_date' => 'datetime',
        'compliance_met' => 'boolean',
        'certification_expiry' => 'date',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function isDue(): bool
    {
        return $this->next_due_date && $this->next_due_date->isPast();
    }

    public function isUpcoming(): bool
    {
        return $this->next_due_date && $this->next_due_date->lessThanOrEqualTo(now()->addDays(7));
    }
}
