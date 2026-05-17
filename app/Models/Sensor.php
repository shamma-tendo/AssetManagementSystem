<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Sensor extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'asset_id',
        'sensor_type_id',
        'name',
        'description',
        'manufacturer',
        'model',
        'serial_number',
        'firmware_version',
        'hardware_version',
        'mac_address',
        'ip_address',
        'location_description',
        'installation_date',
        'calibration_date',
        'next_calibration_date',
        'battery_level',
        'signal_strength',
        'status',
        'configuration',
        'threshold_min',
        'threshold_max',
        'alert_enabled',
        'data_retention_days',
        'sampling_interval',
        'last_data_received',
        'last_heartbeat',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'calibration_date' => 'date',
        'next_calibration_date' => 'date',
        'battery_level' => 'integer',
        'signal_strength' => 'integer',
        'status' => SensorStatus::class,
        'configuration' => 'array',
        'threshold_min' => 'decimal:10,4',
        'threshold_max' => 'decimal:10,4',
        'alert_enabled' => 'boolean',
        'data_retention_days' => 'integer',
        'sampling_interval' => 'integer',
        'last_data_received' => 'datetime',
        'last_heartbeat' => 'datetime',
    ];

    /**
     * Get the asset this sensor is attached to.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the sensor type.
     */
    public function sensorType()
    {
        return $this->belongsTo(SensorType::class);
    }

    /**
     * Get the user who created the sensor.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the sensor.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the sensor readings.
     */
    public function readings()
    {
        return $this->hasMany(SensorReading::class);
    }

    /**
     * Get the sensor alerts.
     */
    public function alerts()
    {
        return $this->hasMany(SensorAlert::class);
    }

    /**
     * Get the maintenance schedules for this sensor.
     */
    public function maintenanceSchedules()
    {
        return $this->hasMany(SensorMaintenanceSchedule::class);
    }

    /**
     * Get the calibration records for this sensor.
     */
    public function calibrationRecords()
    {
        return $this->hasMany(SensorCalibration::class);
    }

    /**
     * Scope a query to only include active sensors.
     */
    public function scopeActive($query)
    {
        return $query->where('status', SensorStatus::ACTIVE);
    }

    /**
     * Scope a query to only include sensors with specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include sensors that need calibration.
     */
    public function scopeNeedsCalibration($query)
    {
        return $query->where('next_calibration_date', '<=', now());
    }

    /**
     * Scope a query to only include sensors with low battery.
     */
    public function scopeLowBattery($query)
    {
        return $query->where('battery_level', '<=', 20);
    }

    /**
     * Scope a query to only include sensors with poor signal.
     */
    public function scopePoorSignal($query)
    {
        return $query->where('signal_strength', '<=', 30);
    }

    /**
     * Scope a query to only include sensors that are offline.
     */
    public function scopeOffline($query)
    {
        return $query->where('last_heartbeat', '<', now()->subMinutes(30));
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return $this->status->getDisplayName();
    }

    /**
     * Get the status color.
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status->getColor();
    }

    /**
     * Check if sensor is active.
     */
    public function isActive(): bool
    {
        return $this->status === SensorStatus::ACTIVE;
    }

    /**
     * Check if sensor is offline.
     */
    public function isOffline(): bool
    {
        return $this->last_heartbeat && $this->last_heartbeat->lt(now()->subMinutes(30));
    }

    /**
     * Check if sensor has low battery.
     */
    public function hasLowBattery(): bool
    {
        return $this->battery_level !== null && $this->battery_level <= 20;
    }

    /**
     * Check if sensor needs calibration.
     */
    public function needsCalibrationCheck(): bool
    {
        return $this->next_calibration_date && $this->next_calibration_date->lte(now());
    }

    /**
     * Check if sensor has poor signal.
     */
    public function hasPoorSignal(): bool
    {
        return $this->signal_strength !== null && $this->signal_strength <= 30;
    }

    /**
     * Get the health status.
     */
    public function getHealthStatusAttribute(): string
    {
        if (!$this->isActive()) {
            return 'inactive';
        } elseif ($this->isOffline()) {
            return 'offline';
        } elseif ($this->hasLowBattery()) {
            return 'low_battery';
        } elseif ($this->hasPoorSignal()) {
            return 'poor_signal';
        } elseif ($this->needsCalibrationCheck()) {
            return 'needs_calibration';
        } else {
            return 'healthy';
        }
    }

    /**
     * Get the health status display.
     */
    public function getHealthStatusDisplayAttribute(): string
    {
        return match($this->health_status) {
            'healthy' => 'Healthy',
            'offline' => 'Offline',
            'low_battery' => 'Low Battery',
            'poor_signal' => 'Poor Signal',
            'needs_calibration' => 'Needs Calibration',
            'inactive' => 'Inactive',
        };
    }

    /**
     * Get the health status color.
     */
    public function getHealthStatusColorAttribute(): string
    {
        return match($this->health_status) {
            'healthy' => 'green',
            'offline' => 'red',
            'low_battery' => 'orange',
            'poor_signal' => 'yellow',
            'needs_calibration' => 'blue',
            'inactive' => 'gray',
        };
    }

    /**
     * Check if value exceeds thresholds.
     */
    public function exceedsThresholds(float $value): string
    {
        if ($this->threshold_min !== null && $value < $this->threshold_min) {
            return 'below_min';
        }
        if ($this->threshold_max !== null && $value > $this->threshold_max) {
            return 'above_max';
        }
        return 'normal';
    }

    /**
     * Get the latest reading.
     */
    public function getLatestReadingAttribute(): ?SensorReading
    {
        return $this->readings()->latest('timestamp')->first();
    }

    /**
     * Get the latest value.
     */
    public function getLatestValueAttribute(): mixed
    {
        return $this->latestReading?->value;
    }

    /**
     * Get the latest timestamp.
     */
    public function getLatestTimestampAttribute(): ?string
    {
        return $this->latestReading?->timestamp->toISOString();
    }

    /**
     * Get readings for the last N hours.
     */
    public function getReadingsLastHours(int $hours = 24)
    {
        return $this->readings()
            ->where('timestamp', '>=', now()->subHours($hours))
            ->orderBy('timestamp')
            ->get();
    }

    /**
     * Get readings for the last N days.
     */
    public function getReadingsLastDays(int $days = 7)
    {
        return $this->readings()
            ->where('timestamp', '>=', now()->subDays($days))
            ->orderBy('timestamp')
            ->get();
    }

    /**
     * Get average value for the last N hours.
     */
    public function getAverageValueLastHours(int $hours = 24): ?float
    {
        return $this->readings()
            ->where('timestamp', '>=', now()->subHours($hours))
            ->avg('value');
    }

    /**
     * Get min/max values for the last N hours.
     */
    public function getMinMaxValueLastHours(int $hours = 24): array
    {
        $readings = $this->getReadingsLastHours($hours);
        
        if ($readings->isEmpty()) {
            return ['min' => null, 'max' => null];
        }

        return [
            'min' => $readings->min('value'),
            'max' => $readings->max('value'),
        ];
    }

    /**
     * Get sensor statistics.
     */
    public function getStatisticsAttribute(): array
    {
        $readings = $this->readings()->limit(1000)->get();
        
        if ($readings->isEmpty()) {
            return [
                'total_readings' => 0,
                'average_value' => null,
                'min_value' => null,
                'max_value' => null,
                'latest_value' => null,
                'latest_timestamp' => null,
                'data_points_today' => 0,
                'data_points_this_week' => 0,
            ];
        }

        return [
            'total_readings' => $this->readings()->count(),
            'average_value' => $readings->avg('value'),
            'min_value' => $readings->min('value'),
            'max_value' => $readings->max('value'),
            'latest_value' => $this->latest_value,
            'latest_timestamp' => $this->latest_timestamp,
            'data_points_today' => $this->readings()->whereDate('timestamp', today())->count(),
            'data_points_this_week' => $this->readings()->whereBetween('timestamp', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
        ];
    }

    /**
     * Get sensor summary.
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'asset_name' => $this->asset->name,
            'sensor_type' => $this->sensorType->name,
            'status' => $this->status_display_name,
            'status_color' => $this->status_color,
            'health_status' => $this->health_status_display,
            'health_color' => $this->health_status_color,
            'latest_value' => $this->latest_value,
            'latest_timestamp' => $this->latest_timestamp,
            'battery_level' => $this->battery_level,
            'signal_strength' => $this->signal_strength,
            'last_heartbeat' => $this->last_heartbeat?->toISOString(),
            'alert_enabled' => $this->alert_enabled,
            'threshold_range' => $this->threshold_min && $this->threshold_max 
                ? "{$this->threshold_min} - {$this->threshold_max}" 
                : 'Not set',
        ];
    }

    /**
     * Create calibration record.
     */
    public function createCalibration(array $calibrationData): SensorCalibration
    {
        $record = $this->calibrationRecords()->create([
            'calibration_date' => now(),
            'performed_by' => auth()->id(),
            'reference_value' => $calibrationData['reference_value'] ?? null,
            'measured_value' => $calibrationData['measured_value'] ?? null,
            'correction_factor' => $calibrationData['correction_factor'] ?? 1.0,
            'notes' => $calibrationData['notes'] ?? null,
        ]);

        // Update next calibration date
        $this->update([
            'calibration_date' => now(),
            'next_calibration_date' => now()->addMonths(6), // Default 6 months
        ]);

        return $record;
    }

    /**
     * Check if sensor needs maintenance.
     */
    public function needsMaintenance(): bool
    {
        return $this->needsCalibrationCheck() || 
               $this->hasLowBattery() || 
               $this->hasPoorSignal() || 
               $this->isOffline();
    }

    /**
     * Get maintenance recommendations.
     */
    public function getMaintenanceRecommendationsAttribute(): array
    {
        $recommendations = [];

        if ($this->needsCalibrationCheck()) {
            $recommendations[] = [
                'type' => 'calibration',
                'priority' => 'medium',
                'message' => 'Sensor requires calibration',
                'action' => 'Schedule calibration maintenance',
            ];
        }

        if ($this->hasLowBattery()) {
            $recommendations[] = [
                'type' => 'battery',
                'priority' => 'high',
                'message' => 'Battery level is low',
                'action' => 'Replace or recharge battery',
            ];
        }

        if ($this->hasPoorSignal()) {
            $recommendations[] = [
                'type' => 'signal',
                'priority' => 'medium',
                'message' => 'Signal strength is poor',
                'action' => 'Check antenna placement or interference',
            ];
        }

        if ($this->isOffline()) {
            $recommendations[] = [
                'type' => 'connectivity',
                'priority' => 'high',
                'message' => 'Sensor is offline',
                'action' => 'Check power and network connectivity',
            ];
        }

        return $recommendations;
    }
}

/**
 * Sensor Status Enum
 */
enum SensorStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MAINTENANCE = 'maintenance';
    case ERROR = 'error';
    case CALIBRATING = 'calibrating';
    case OFFLINE = 'offline';

    public function getDisplayName(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::MAINTENANCE => 'Maintenance',
            self::ERROR => 'Error',
            self::CALIBRATING => 'Calibrating',
            self::OFFLINE => 'Offline',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::MAINTENANCE => 'blue',
            self::ERROR => 'red',
            self::CALIBRATING => 'yellow',
            self::OFFLINE => 'red',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::ACTIVE => 'check-circle',
            self::INACTIVE => 'pause-circle',
            self::MAINTENANCE => 'wrench',
            self::ERROR => 'alert-circle',
            self::CALIBRATING => 'settings',
            self::OFFLINE => 'wifi-off',
        };
    }

    public function isOperational(): bool
    {
        return in_array($this, [self::ACTIVE, self::CALIBRATING]);
    }

    public function needsAttention(): bool
    {
        return in_array($this, [self::ERROR, self::OFFLINE, self::MAINTENANCE]);
    }
}
