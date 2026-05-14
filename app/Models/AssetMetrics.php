<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMetrics extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'metric_date',
        'total_assets',
        'assets_in_use',
        'utilization_rate',
        'unused_assets',
        'damaged_assets',
        'stolen_assets',
        'loss_rate',
        'total_loss_value',
        'total_asset_value',
        'total_depreciation_value',
        'net_asset_value',
        'replacement_cost',
        'maintenance_cost_ytd',
        'depreciation_cost_ytd',
        'cost_per_asset',
        'assets_needing_repair',
        'overdue_maintenance',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'total_assets' => 'integer',
        'assets_in_use' => 'integer',
        'utilization_rate' => 'decimal:2',
        'unused_assets' => 'integer',
        'damaged_assets' => 'integer',
        'stolen_assets' => 'integer',
        'loss_rate' => 'decimal:2',
        'total_loss_value' => 'decimal:2',
        'total_asset_value' => 'decimal:2',
        'total_depreciation_value' => 'decimal:2',
        'net_asset_value' => 'decimal:2',
        'replacement_cost' => 'decimal:2',
        'maintenance_cost_ytd' => 'decimal:2',
        'depreciation_cost_ytd' => 'decimal:2',
        'cost_per_asset' => 'decimal:2',
        'assets_needing_repair' => 'integer',
        'overdue_maintenance' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function getHealthScoreAttribute(): float
    {
        // Health score based on various metrics (0-100)
        $score = 100;
        
        // Deduct for low utilization
        if ($this->utilization_rate < 50) {
            $score -= (50 - $this->utilization_rate) * 0.5;
        }
        
        // Deduct for losses
        if ($this->loss_rate > 0) {
            $score -= $this->loss_rate * 2;
        }
        
        // Deduct for maintenance backlog
        if ($this->overdue_maintenance > 0) {
            $score -= min($this->overdue_maintenance * 5, 30);
        }
        
        return max(0, min(100, $score));
    }
}
