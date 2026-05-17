<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SensorAlertTemplate extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'sensor_type_id',
        'alert_type',
        'severity',
        'name',
        'description',
        'condition_template',
        'message_template',
        'threshold_min',
        'threshold_max',
        'duration_threshold',
        'auto_escalate',
        'escalation_rules',
        'notification_channels',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'threshold_min' => 'decimal:10,4',
        'threshold_max' => 'decimal:10,4',
        'duration_threshold' => 'integer',
        'auto_escalate' => 'boolean',
        'escalation_rules' => 'array',
        'notification_channels' => 'array',
        'is_active' => 'boolean',
        'alert_type' => AlertType::class,
        'severity' => AlertSeverity::class,
    ];

    /**
     * Get the sensor type for this template.
     */
    public function sensorType()
    {
        return $this->belongsTo(SensorType::class);
    }

    /**
     * Get the user who created the template.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the template.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include templates for specific alert type.
     */
    public function scopeByAlertType($query, $alertType)
    {
        return $query->where('alert_type', $alertType);
    }

    /**
     * Get the alert type display name.
     */
    public function getAlertTypeDisplayNameAttribute(): string
    {
        return $this->alert_type->getDisplayName();
    }

    /**
     * Get the severity display name.
     */
    public function getSeverityDisplayNameAttribute(): string
    {
        return $this->severity->getDisplayName();
    }

    /**
     * Get the formatted threshold range.
     */
    public function getFormattedThresholdRangeAttribute(): string
    {
        if ($this->threshold_min !== null && $this->threshold_max !== null) {
            return "{$this->threshold_min} - {$this->threshold_max}";
        } elseif ($this->threshold_min !== null) {
            return "≥ {$this->threshold_min}";
        } elseif ($this->threshold_max !== null) {
            return "≤ {$this->threshold_max}";
        } else {
            return 'No threshold';
        }
    }

    /**
     * Check if value matches template conditions.
     */
    public function matchesConditions(float $value): bool
    {
        return match($this->alert_type) {
            AlertType::THRESHOLD_HIGH => $this->threshold_max !== null && $value > $this->threshold_max,
            AlertType::THRESHOLD_LOW => $this->threshold_min !== null && $value < $this->threshold_min,
            default => false,
        };
    }

    /**
     * Generate alert message using template.
     */
    public function generateMessage(array $variables): string
    {
        $message = $this->message_template;
        
        foreach ($variables as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        
        return $message;
    }

    /**
     * Get template summary.
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sensor_type' => $this->sensorType->name,
            'alert_type' => $this->alert_type_display_name,
            'severity' => $this->severity_display_name,
            'threshold_range' => $this->formatted_threshold_range,
            'duration_threshold' => $this->duration_threshold,
            'auto_escalate' => $this->auto_escalate,
            'notification_channels' => $this->notification_channels,
            'is_active' => $this->is_active,
        ];
    }
}
