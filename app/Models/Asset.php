<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

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
     * Get the asset formatted for display in views/APIs.
     */
    public function getFormattedAttribute(): array
    {
        $sv = $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status;
        $statusMap = [
            'ordered' => 'ORDERED',
            'received' => 'RECEIVED',
            'active' => 'ACTIVE',
            'under_maintenance' => 'IN REPAIR',
            'retired' => 'RETIRED',
            'disposed' => 'DISPOSED',
        ];

        $lastMaintDate = $this->maintenanceHistories()->max('performed_date');

        return [
            'id' => $this->serial_number,
            'name' => $this->name,
            'category' => $this->category?->name ?? 'Uncategorized',
            'location' => $this->location?->name ?? 'N/A',
            'health' => $this->calculateHealth($lastMaintDate),
            'status' => $statusMap[$sv] ?? strtoupper($sv),
            'lastMaintenance' => $lastMaintDate ?? $this->purchase_date?->format('Y-m-d') ?? 'N/A',
            'manufacturer' => $this->manufacturer ?? 'N/A',
            'installedDate' => $this->purchase_date?->format('Y-m-d') ?? 'N/A',
            'warrantyEnd' => $this->warranty_expiry?->format('Y-m-d') ?? 'N/A',
        ];
    }

    /**
     * Calculate a real health score (0-100) from three weighted factors:
     *   40% — age vs. useful life (depreciation-based)
     *   40% — maintenance recency (days since last completed maintenance)
     *   20% — current operational status
     */
    public function calculateHealth(?string $lastMaintDate = null): int
    {
        $sv = $this->status->value;

        // --- Status factor (20%) ---
        $statusScore = match($sv) {
            'ordered', 'received'  => 100,
            'active'               => 100,
            'under_maintenance'    => 60,
            'retired'              => 20,
            'disposed'             => 0,
            default                => 80,
        };

        // Short-circuit: retired / disposed assets don't need a detailed score
        if (in_array($sv, ['retired', 'disposed'])) {
            return $statusScore;
        }

        // New assets (ordered/received) are considered fully healthy
        if (in_array($sv, ['ordered', 'received'])) {
            return 100;
        }

        // --- Age factor (40%) ---
        $usefulLife = max(1, $this->useful_life_years ?? 10);
        $ageYears   = $this->purchase_date
            ? $this->purchase_date->diffInDays(now()) / 365.25
            : $usefulLife * 0.5; // assume mid-life if unknown
        $ageScore = (int) round(max(0, (1 - min(1.0, $ageYears / $usefulLife)) * 100));

        // --- Maintenance recency factor (40%) ---
        if ($lastMaintDate) {
            $daysSince = now()->diffInDays(Carbon::parse($lastMaintDate));
        } else {
            // No recorded maintenance — penalise based on asset age
            $daysSince = $this->purchase_date
                ? $this->purchase_date->diffInDays(now())
                : 730;
        }

        $maintenanceScore = match(true) {
            $daysSince <= 30  => 100,
            $daysSince <= 90  => 85,
            $daysSince <= 180 => 70,
            $daysSince <= 365 => 50,
            $daysSince <= 730 => 30,
            default           => 10,
        };

        // Weighted average: 40% age + 40% maintenance + 20% status
        return (int) round(($ageScore * 0.4) + ($maintenanceScore * 0.4) + ($statusScore * 0.2));
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
