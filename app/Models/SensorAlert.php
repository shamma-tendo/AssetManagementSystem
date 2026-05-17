<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SensorAlert extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'sensor_id',
        'alert_type',
        'severity',
        'message',
        'description',
        'trigger_value',
        'threshold_value',
        'triggered_at',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'metadata',
        'auto_resolved',
        'escalation_level',
        'notification_sent',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'auto_resolved' => 'boolean',
        'escalation_level' => 'integer',
        'notification_sent' => 'boolean',
        'alert_type' => AlertType::class,
        'severity' => AlertSeverity::class,
        'metadata' => 'array',
    ];

    /**
     * Get the sensor that triggered this alert.
     */
    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    /**
     * Get the user who acknowledged the alert.
     */
    public function acknowledger()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Get the user who resolved the alert.
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope a query to only include unacknowledged alerts.
     */
    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    /**
     * Scope a query to only include unresolved alerts.
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope a query to only include alerts of specific type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope a query to only include alerts of specific severity.
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to only include alerts within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('triggered_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include critical alerts.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', AlertSeverity::CRITICAL);
    }

    /**
     * Get the alert type display name.
     */
    public function getAlertTypeDisplayNameAttribute(): string
    {
        return $this->alert_type->getDisplayName();
    }

    /**
     * Get the alert type color.
     */
    public function getAlertTypeColorAttribute(): string
    {
        return $this->alert_type->getColor();
    }

    /**
     * Get the severity display name.
     */
    public function getSeverityDisplayNameAttribute(): string
    {
        return $this->severity->getDisplayName();
    }

    /**
     * Get the severity color.
     */
    public function getSeverityColorAttribute(): string
    {
        return $this->severity->getColor();
    }

    /**
     * Check if alert is acknowledged.
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /**
     * Check if alert is resolved.
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Check if alert is active (unacknowledged and unresolved).
     */
    public function isActive(): bool
    {
        return !$this->isAcknowledged() && !$this->isResolved();
    }

    /**
     * Get the alert status.
     */
    public function getAlertStatusAttribute(): string
    {
        if ($this->isResolved()) {
            return 'resolved';
        } elseif ($this->isAcknowledged()) {
            return 'acknowledged';
        } else {
            return 'active';
        }
    }

    /**
     * Get the alert status display.
     */
    public function getAlertStatusDisplayAttribute(): string
    {
        return match($this->alert_status) {
            'active' => 'Active',
            'acknowledged' => 'Acknowledged',
            'resolved' => 'Resolved',
        };
    }

    /**
     * Get the alert status color.
     */
    public function getAlertStatusColorAttribute(): string
    {
        return match($this->alert_status) {
            'active' => 'red',
            'acknowledged' => 'yellow',
            'resolved' => 'green',
        };
    }

    /**
     * Get the duration since triggered.
     */
    public function getDurationAttribute(): string
    {
        $now = now();
        $triggered = $this->triggered_at;
        
        if ($this->isResolved()) {
            $end = $this->resolved_at;
        } elseif ($this->isAcknowledged()) {
            $end = $this->acknowledged_at;
        } else {
            $end = $now;
        }

        return $triggered->diffForHumans($end, true);
    }

    /**
     * Get the time to acknowledge.
     */
    public function getTimeToAcknowledgeAttribute(): ?string
    {
        if (!$this->isAcknowledged()) {
            return null;
        }

        return $this->triggered_at->diffForHumans($this->acknowledged_at, true);
    }

    /**
     * Get the time to resolve.
     */
    public function getTimeToResolveAttribute(): ?string
    {
        if (!$this->isResolved()) {
            return null;
        }

        return $this->triggered_at->diffForHumans($this->resolved_at, true);
    }

    /**
     * Acknowledge the alert.
     */
    public function acknowledge(User $user, string $notes = null): void
    {
        $this->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
            'metadata' => array_merge($this->metadata ?? [], [
                'acknowledgment_notes' => $notes,
            ]),
        ]);
    }

    /**
     * Resolve the alert.
     */
    public function resolve(User $user, string $resolutionNotes = null): void
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by' => $user->id,
            'resolution_notes' => $resolutionNotes,
        ]);
    }

    /**
     * Auto-resolve the alert.
     */
    public function autoResolve(string $reason = null): void
    {
        $this->update([
            'resolved_at' => now(),
            'auto_resolved' => true,
            'resolution_notes' => $reason ?? 'Automatically resolved',
        ]);
    }

    /**
     * Escalate the alert.
     */
    public function escalate(): void
    {
        $this->update([
            'escalation_level' => $this->escalation_level + 1,
        ]);
    }

    /**
     * Get alert summary.
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'sensor_name' => $this->sensor->name,
            'asset_name' => $this->sensor->asset->name,
            'alert_type' => $this->alert_type_display_name,
            'severity' => $this->severity_display_name,
            'severity_color' => $this->severity_color,
            'message' => $this->message,
            'trigger_value' => $this->trigger_value,
            'threshold_value' => $this->threshold_value,
            'status' => $this->alert_status_display,
            'status_color' => $this->alert_status_color,
            'triggered_at' => $this->triggered_at->toISOString(),
            'duration' => $this->duration,
            'acknowledged_at' => $this->acknowledged_at?->toISOString(),
            'acknowledged_by' => $this->acknowledger?->full_name,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'resolved_by' => $this->resolver?->full_name,
            'escalation_level' => $this->escalation_level,
        ];
    }
}

/**
 * Alert Type Enum
 */
enum AlertType: string
{
    case THRESHOLD_HIGH = 'threshold_high';
    case THRESHOLD_LOW = 'threshold_low';
    case ANOMALY = 'anomaly';
    case QUALITY = 'quality';
    case OFFLINE = 'offline';
    case LOW_BATTERY = 'low_battery';
    case POOR_SIGNAL = 'poor_signal';
    case CALIBRATION_DUE = 'calibration_due';
    case MAINTENANCE_DUE = 'maintenance_due';
    case COMMUNICATION_ERROR = 'communication_error';
    case SENSOR_ERROR = 'sensor_error';
    case DATA_GAP = 'data_gap';
    case SYSTEM_ERROR = 'system_error';

    public function getDisplayName(): string
    {
        return match($this) {
            self::THRESHOLD_HIGH => 'High Threshold',
            self::THRESHOLD_LOW => 'Low Threshold',
            self::ANOMALY => 'Anomaly Detected',
            self::QUALITY => 'Poor Data Quality',
            self::OFFLINE => 'Sensor Offline',
            self::LOW_BATTERY => 'Low Battery',
            self::POOR_SIGNAL => 'Poor Signal',
            self::CALIBRATION_DUE => 'Calibration Due',
            self::MAINTENANCE_DUE => 'Maintenance Due',
            self::COMMUNICATION_ERROR => 'Communication Error',
            self::SENSOR_ERROR => 'Sensor Error',
            self::DATA_GAP => 'Data Gap',
            self::SYSTEM_ERROR => 'System Error',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::THRESHOLD_HIGH => 'red',
            self::THRESHOLD_LOW => 'orange',
            self::ANOMALY => 'yellow',
            self::QUALITY => 'yellow',
            self::OFFLINE => 'red',
            self::LOW_BATTERY => 'orange',
            self::POOR_SIGNAL => 'yellow',
            self::CALIBRATION_DUE => 'blue',
            self::MAINTENANCE_DUE => 'blue',
            self::COMMUNICATION_ERROR => 'red',
            self::SENSOR_ERROR => 'red',
            self::DATA_GAP => 'orange',
            self::SYSTEM_ERROR => 'red',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::THRESHOLD_HIGH => 'trending-up',
            self::THRESHOLD_LOW => 'trending-down',
            self::ANOMALY => 'alert-triangle',
            self::QUALITY => 'alert-circle',
            self::OFFLINE => 'wifi-off',
            self::LOW_BATTERY => 'battery-low',
            self::POOR_SIGNAL => 'wifi',
            self::CALIBRATION_DUE => 'settings',
            self::MAINTENANCE_DUE => 'wrench',
            self::COMMUNICATION_ERROR => 'radio',
            self::SENSOR_ERROR => 'cpu',
            self::DATA_GAP => 'bar-chart',
            self::SYSTEM_ERROR => 'server',
        };
    }
}

/**
 * Alert Severity Enum
 */
enum AlertSeverity: string
{
    case INFO = 'info';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function getDisplayName(): string
    {
        return match($this) {
            self::INFO => 'Info',
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::CRITICAL => 'Critical',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::INFO => 'blue',
            self::LOW => 'green',
            self::MEDIUM => 'yellow',
            self::HIGH => 'orange',
            self::CRITICAL => 'red',
        };
    }

    public function getLevel(): int
    {
        return match($this) {
            self::INFO => 1,
            self::LOW => 2,
            self::MEDIUM => 3,
            self::HIGH => 4,
            self::CRITICAL => 5,
        };
    }

    public function requiresImmediateAction(): bool
    {
        return in_array($this, [self::HIGH, self::CRITICAL]);
    }

    public function getAutoResolveTimeout(): int
    {
        return match($this) {
            self::INFO => 3600, // 1 hour
            self::LOW => 7200, // 2 hours
            self::MEDIUM => 14400, // 4 hours
            self::HIGH => 28800, // 8 hours
            self::CRITICAL => 0, // Never auto-resolve
        };
    }
}
