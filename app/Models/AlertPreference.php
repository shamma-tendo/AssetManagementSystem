<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertPreference extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'organization_id',
        'email_alerts',
        'push_notifications',
        'maintenance_alerts',
        'asset_overdue_alerts',
        'high_value_alerts',
        'damage_alerts',
        'daily_digest',
    ];

    protected $casts = [
        'email_alerts' => 'boolean',
        'push_notifications' => 'boolean',
        'maintenance_alerts' => 'boolean',
        'asset_overdue_alerts' => 'boolean',
        'high_value_alerts' => 'boolean',
        'damage_alerts' => 'boolean',
        'daily_digest' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
