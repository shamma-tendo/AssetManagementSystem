<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SensorReading extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'sensor_id',
        'timestamp',
        'value',
        'unit',
        'quality',
        'raw_data',
        'processed_data',
        'metadata',
        'battery_level',
        'signal_strength',
        'temperature',
        'humidity',
        'error_code',
        'status_flags',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'value' => 'decimal:15,6',
        'quality' => 'decimal:5,4',
        'raw_data' => 'array',
        'processed_data' => 'array',
        'metadata' => 'array',
        'battery_level' => 'integer',
        'signal_strength' => 'integer',
        'temperature' => 'decimal:5,2',
        'humidity' => 'decimal:5,2',
        'error_code' => 'integer',
        'status_flags' => 'array',
        'status' => ReadingStatus::class,
    ];

    /**
     * Get the sensor for this reading.
     */
    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    /**
     * Scope a query to only include readings within date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include readings for the last N hours.
     */
    public function scopeLastHours($query, $hours)
    {
        return $query->where('timestamp', '>=', now()->subHours($hours));
    }

    /**
     * Scope a query to only include readings for the last N days.
     */
    public function scopeLastDays($query, $days)
    {
        return $query->where('timestamp', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to only include readings with good quality.
     */
    public function scopeGoodQuality($query)
    {
        return $query->where('quality', '>=', 0.8);
    }

    /**
     * Scope a query to only include readings with poor quality.
     */
    public function scopePoorQuality($query)
    {
        return $query->where('quality', '<', 0.5);
    }

    /**
     * Scope a query to only include readings with errors.
     */
    public function scopeWithErrors($query)
    {
        return $query->whereNotNull('error_code')
                    ->where('error_code', '!=', 0);
    }

    /**
     * Get the formatted value.
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->sensor->sensorType->data_type->isNumeric()) {
            return number_format($this->value, 2) . ' ' . ($this->unit ?? $this->sensor->sensorType->unit_of_measure);
        }
        
        return (string) $this->value;
    }

    /**
     * Get the quality status.
     */
    public function getQualityStatusAttribute(): string
    {
        if ($this->quality >= 0.9) {
            return 'excellent';
        } elseif ($this->quality >= 0.8) {
            return 'good';
        } elseif ($this->quality >= 0.6) {
            return 'fair';
        } elseif ($this->quality >= 0.4) {
            return 'poor';
        } else {
            return 'bad';
        }
    }

    /**
     * Get the quality status display.
     */
    public function getQualityStatusDisplayAttribute(): string
    {
        return match($this->quality_status) {
            'excellent' => 'Excellent',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
            'bad' => 'Bad',
        };
    }

    /**
     * Get the quality status color.
     */
    public function getQualityStatusColorAttribute(): string
    {
        return match($this->quality_status) {
            'excellent' => 'green',
            'good' => 'blue',
            'fair' => 'yellow',
            'poor' => 'orange',
            'bad' => 'red',
        };
    }

    /**
     * Check if reading has errors.
     */
    public function hasErrors(): bool
    {
        return $this->error_code !== null && $this->error_code !== 0;
    }

    /**
     * Check if reading has good quality.
     */
    public function hasGoodQuality(): bool
    {
        return $this->quality >= 0.8;
    }

    /**
     * Check if reading is anomalous (compared to recent readings).
     */
    public function isAnomalous(float $threshold = 2.0): bool
    {
        // Get recent readings for comparison
        $recentReadings = $this->sensor->readings()
            ->where('timestamp', '<', $this->timestamp)
            ->where('timestamp', '>=', $this->timestamp->subHours(24))
            ->where('quality', '>=', 0.8)
            ->limit(20)
            ->pluck('value');

        if ($recentReadings->count() < 5) {
            return false;
        }

        $mean = $recentReadings->avg();
        $stdDev = $this->calculateStandardDeviation($recentReadings->toArray());
        
        if ($stdDev == 0) {
            return false;
        }

        $zScore = abs($this->value - $mean) / $stdDev;
        
        return $zScore > $threshold;
    }

    /**
     * Calculate standard deviation.
     */
    private function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0;
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values)) / ($count - 1);

        return sqrt($variance);
    }

    /**
     * Get reading summary.
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'sensor_name' => $this->sensor->name,
            'sensor_type' => $this->sensor->sensorType->name,
            'timestamp' => $this->timestamp->toISOString(),
            'value' => $this->formatted_value,
            'quality' => $this->quality_status_display,
            'quality_color' => $this->quality_status_color,
            'battery_level' => $this->battery_level,
            'signal_strength' => $this->signal_strength,
            'has_errors' => $this->hasErrors(),
            'error_code' => $this->error_code,
            'is_anomalous' => $this->isAnomalous(),
        ];
    }

    /**
     * Create reading with validation.
     */
    public static function createValidated(array $data): self
    {
        $sensor = Sensor::findOrFail($data['sensor_id']);
        
        // Validate value range
        if ($sensor->sensorType->min_value !== null && $data['value'] < $sensor->sensorType->min_value) {
            throw new \InvalidArgumentException("Value {$data['value']} is below minimum {$sensor->sensorType->min_value}");
        }
        
        if ($sensor->sensorType->max_value !== null && $data['value'] > $sensor->sensorType->max_value) {
            throw new \InvalidArgumentException("Value {$data['value']} is above maximum {$sensor->sensorType->max_value}");
        }

        // Set default quality if not provided
        $data['quality'] ??= 1.0;
        $data['timestamp'] ??= now();
        $data['unit'] ??= $sensor->sensorType->unit_of_measure;

        $reading = self::create($data);

        // Update sensor last data received
        $sensor->update(['last_data_received' => now()]);

        // Check for alerts
        if ($sensor->alert_enabled) {
            $reading->checkForAlerts();
        }

        return $reading;
    }

    /**
     * Check for alerts based on reading value.
     */
    private function checkForAlerts(): void
    {
        $sensor = $this->sensor;
        $thresholdStatus = $sensor->exceedsThresholds($this->value);

        if ($thresholdStatus !== 'normal') {
            SensorAlert::create([
                'sensor_id' => $sensor->id,
                'alert_type' => $thresholdStatus === 'below_min' ? AlertType::THRESHOLD_LOW : AlertType::THRESHOLD_HIGH,
                'severity' => AlertSeverity::WARNING,
                'message' => "Sensor {$sensor->name} value {$this->formatted_value} exceeds threshold",
                'trigger_value' => $this->value,
                'threshold_value' => $thresholdStatus === 'below_min' ? $sensor->threshold_min : $sensor->threshold_max,
                'triggered_at' => $this->timestamp,
                'acknowledged' => false,
            ]);
        }

        // Check for anomalous readings
        if ($this->isAnomalous()) {
            SensorAlert::create([
                'sensor_id' => $sensor->id,
                'alert_type' => AlertType::ANOMALY,
                'severity' => AlertSeverity::INFO,
                'message' => "Anomalous reading detected from sensor {$sensor->name}",
                'trigger_value' => $this->value,
                'triggered_at' => $this->timestamp,
                'acknowledged' => false,
            ]);
        }

        // Check for poor quality
        if ($this->quality < 0.5) {
            SensorAlert::create([
                'sensor_id' => $sensor->id,
                'alert_type' => AlertType::QUALITY,
                'severity' => AlertSeverity::WARNING,
                'message' => "Poor data quality from sensor {$sensor->name}",
                'trigger_value' => $this->quality,
                'triggered_at' => $this->timestamp,
                'acknowledged' => false,
            ]);
        }
    }
}

/**
 * Reading Status Enum
 */
enum ReadingStatus: string
{
    case VALID = 'valid';
    case INVALID = 'invalid';
    case QUESTIONABLE = 'questionable';
    case PROCESSED = 'processed';
    case ERROR = 'error';

    public function getDisplayName(): string
    {
        return match($this) {
            self::VALID => 'Valid',
            self::INVALID => 'Invalid',
            self::QUESTIONABLE => 'Questionable',
            self::PROCESSED => 'Processed',
            self::ERROR => 'Error',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::VALID => 'green',
            self::INVALID => 'red',
            self::QUESTIONABLE => 'yellow',
            self::PROCESSED => 'blue',
            self::ERROR => 'red',
        };
    }
}
