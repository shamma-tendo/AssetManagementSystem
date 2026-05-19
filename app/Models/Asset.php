<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Asset extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'serial_number',
        'category_id',
        'location_id',
        'department_id',
        'purchase_date',
        'purchase_cost',
        'current_value',
        'salvage_value',
        'useful_life_years',
        'depreciation_method',
        'status',
        'description',
        'manufacturer',
        'model',
        'warranty_expiry',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_cost' => 'decimal:2',
        'current_value' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'useful_life_years' => 'integer',
        'status' => AssetStatus::class,
        'depreciation_method' => DepreciationMethod::class,
    ];

    /**
     * Get the category that owns the asset.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the location that owns the asset.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the department that owns the asset.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the maintenance history records for this asset.
     */
    public function maintenanceHistories()
    {
        return $this->hasMany(MaintenanceHistory::class);
    }

    /**
     * Get the maintenance schedules for this asset.
     */
    public function maintenanceSchedules()
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    /**
     * Get the inspections for this asset.
     */
    public function inspections()
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * Get the depreciation schedule for this asset.
     */
    public function depreciation()
    {
        return $this->hasOne(AssetDepreciation::class);
    }

    /**
     * Get the depreciation entries for this asset.
     */
    public function depreciationEntries()
    {
        return $this->hasManyThrough(DepreciationEntry::class, AssetDepreciation::class);
    }

    /**
     * Get the sensors attached to this asset.
     */
    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    /**
     * Get the sensor readings for this asset.
     */
    public function sensorReadings()
    {
        return $this->hasManyThrough(SensorReading::class, Sensor::class);
    }

    /**
     * Get the sensor alerts for this asset.
     */
    public function sensorAlerts()
    {
        return $this->hasManyThrough(SensorAlert::class, Sensor::class);
    }

    /**
     * Get the user who created the asset.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the asset.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active assets.
     */
    public function scopeActive($query)
    {
        return $query->where('status', AssetStatus::ACTIVE);
    }

    /**
     * Scope a query to only include assets under maintenance.
     */
    public function scopeUnderMaintenance($query)
    {
        return $query->where('status', AssetStatus::UNDER_MAINTENANCE);
    }

    /**
     * Scope a query to only include retired assets.
     */
    public function scopeRetired($query)
    {
        return $query->where('status', AssetStatus::RETIRED);
    }

    /**
     * Get the asset's current status display name.
     */
    public function getStatusDisplayNameAttribute()
    {
        return $this->status->getDisplayName();
    }

    /**
     * Calculate the current depreciation amount.
     */
    public function calculateDepreciation()
    {
        $years = $this->purchase_date->diffInYears(now());
        $cost = $this->purchase_cost;
        $salvage = $this->salvage_value ?? ($cost * 0.1);
        $life = $this->useful_life_years;

        if ($years >= $life) {
            return $salvage;
        }

        return match($this->depreciation_method) {
            DepreciationMethod::STRAIGHT_LINE => $cost - (($cost - $salvage) / $life * $years),
            DepreciationMethod::DECLINING_BALANCE => $cost * pow(1 - (2 / $life), $years),
            default => $cost,
        };
    }
}

/**
 * Asset Status Enum
 */
enum AssetStatus: string
{
    case ORDERED = 'ordered';
    case RECEIVED = 'received';
    case ACTIVE = 'active';
    case UNDER_MAINTENANCE = 'under_maintenance';
    case RETIRED = 'retired';
    case DISPOSED = 'disposed';

    public function getDisplayName(): string
    {
        return match($this) {
            self::ORDERED => 'Ordered',
            self::RECEIVED => 'Received',
            self::ACTIVE => 'Active',
            self::UNDER_MAINTENANCE => 'Under Maintenance',
            self::RETIRED => 'Retired',
            self::DISPOSED => 'Disposed',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::ORDERED => 'gray',
            self::RECEIVED => 'blue',
            self::ACTIVE => 'green',
            self::UNDER_MAINTENANCE => 'yellow',
            self::RETIRED => 'orange',
            self::DISPOSED => 'red',
        };
    }
}

/**
 * Depreciation Method Enum
 */
enum DepreciationMethod: string
{
    case STRAIGHT_LINE = 'straight_line';
    case DECLINING_BALANCE = 'declining_balance';

    public function getDisplayName(): string
    {
        return match($this) {
            self::STRAIGHT_LINE => 'Straight Line',
            self::DECLINING_BALANCE => 'Declining Balance',
        };
    }
}
