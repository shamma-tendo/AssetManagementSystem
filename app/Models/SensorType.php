<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SensorType extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'category',
        'unit_of_measure',
        'data_type',
        'min_value',
        'max_value',
        'default_threshold_min',
        'default_threshold_max',
        'sampling_frequency',
        'communication_protocol',
        'power_requirements',
        'environmental_specs',
        'accuracy',
        'resolution',
        'response_time',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_value' => 'decimal:10,4',
        'max_value' => 'decimal:10,4',
        'default_threshold_min' => 'decimal:10,4',
        'default_threshold_max' => 'decimal:10,4',
        'sampling_frequency' => 'integer',
        'accuracy' => 'decimal:5,4',
        'resolution' => 'decimal:10,6',
        'response_time' => 'integer',
        'environmental_specs' => 'array',
        'is_active' => 'boolean',
        'data_type' => SensorDataType::class,
    ];

    /**
     * Get the user who created the sensor type.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the sensor type.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the sensors of this type.
     */
    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    /**
     * Get the alert templates for this sensor type.
     */
    public function alertTemplates()
    {
        return $this->hasMany(SensorAlertTemplate::class);
    }

    /**
     * Scope a query to only include active sensor types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include sensor types in specific category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get the full description.
     */
    public function getFullDescriptionAttribute(): string
    {
        return "{$this->name} ({$this->category}) - {$this->description}";
    }

    /**
     * Check if value is within acceptable range.
     */
    public function isValueInRange(float $value): bool
    {
        if ($this->min_value !== null && $value < $this->min_value) {
            return false;
        }
        if ($this->max_value !== null && $value > $this->max_value) {
            return false;
        }
        return true;
    }

    /**
     * Check if value exceeds default thresholds.
     */
    public function exceedsDefaultThresholds(float $value): string
    {
        if ($this->default_threshold_min !== null && $value < $this->default_threshold_min) {
            return 'below_min';
        }
        if ($this->default_threshold_max !== null && $value > $this->default_threshold_max) {
            return 'above_max';
        }
        return 'normal';
    }

    /**
     * Get the formatted range.
     */
    public function getFormattedRangeAttribute(): string
    {
        if ($this->min_value !== null && $this->max_value !== null) {
            return "{$this->min_value} - {$this->max_value} {$this->unit_of_measure}";
        } elseif ($this->min_value !== null) {
            return "≥ {$this->min_value} {$this->unit_of_measure}";
        } elseif ($this->max_value !== null) {
            return "≤ {$this->max_value} {$this->unit_of_measure}";
        } else {
            return "No range limit";
        }
    }

    /**
     * Get the formatted threshold range.
     */
    public function getFormattedThresholdRangeAttribute(): string
    {
        if ($this->default_threshold_min !== null && $this->default_threshold_max !== null) {
            return "{$this->default_threshold_min} - {$this->default_threshold_max} {$this->unit_of_measure}";
        } elseif ($this->default_threshold_min !== null) {
            return "≥ {$this->default_threshold_min} {$this->unit_of_measure}";
        } elseif ($this->default_threshold_max !== null) {
            return "≤ {$this->default_threshold_max} {$this->unit_of_measure}";
        } else {
            return "No threshold set";
        }
    }
}

/**
 * Sensor Data Type Enum
 */
enum SensorDataType: string
{
    case TEMPERATURE = 'temperature';
    case HUMIDITY = 'humidity';
    case PRESSURE = 'pressure';
    case VOLTAGE = 'voltage';
    case CURRENT = 'current';
    case POWER = 'power';
    case ENERGY = 'energy';
    case VIBRATION = 'vibration';
    case MOTION = 'motion';
    case LIGHT = 'light';
    case SOUND = 'sound';
    case FLOW = 'flow';
    case LEVEL = 'level';
    case POSITION = 'position';
    case SPEED = 'speed';
    case ACCELERATION = 'acceleration';
    case ROTATION = 'rotation';
    case TORQUE = 'torque';
    case FORCE = 'force';
    case STRAIN = 'strain';
    case PH = 'ph';
    case CONDUCTIVITY = 'conductivity';
    case TURBIDITY = 'turbidity';
    case GAS = 'gas';
    case RADIATION = 'radiation';
    case MAGNETIC = 'magnetic';
    case PROXIMITY = 'proximity';
    case DISTANCE = 'distance';
    case ANGLE = 'angle';
    case WEIGHT = 'weight';
    case MASS = 'mass';
    case VOLUME = 'volume';
    case DENSITY = 'density';
    case CONCENTRATION = 'concentration';
    case BOOLEAN = 'boolean';
    case COUNTER = 'counter';
    case ENUM = 'enum';
    case TEXT = 'text';
    case BINARY = 'binary';
    case JSON = 'json';

    public function getDisplayName(): string
    {
        return match($this) {
            self::TEMPERATURE => 'Temperature',
            self::HUMIDITY => 'Humidity',
            self::PRESSURE => 'Pressure',
            self::VOLTAGE => 'Voltage',
            self::CURRENT => 'Current',
            self::POWER => 'Power',
            self::ENERGY => 'Energy',
            self::VIBRATION => 'Vibration',
            self::MOTION => 'Motion',
            self::LIGHT => 'Light',
            self::SOUND => 'Sound',
            self::FLOW => 'Flow',
            self::LEVEL => 'Level',
            self::POSITION => 'Position',
            self::SPEED => 'Speed',
            self::ACCELERATION => 'Acceleration',
            self::ROTATION => 'Rotation',
            self::TORQUE => 'Torque',
            self::FORCE => 'Force',
            self::STRAIN => 'Strain',
            self::PH => 'pH Level',
            self::CONDUCTIVITY => 'Conductivity',
            self::TURBIDITY => 'Turbidity',
            self::GAS => 'Gas',
            self::RADIATION => 'Radiation',
            self::MAGNETIC => 'Magnetic',
            self::PROXIMITY => 'Proximity',
            self::DISTANCE => 'Distance',
            self::ANGLE => 'Angle',
            self::WEIGHT => 'Weight',
            self::MASS => 'Mass',
            self::VOLUME => 'Volume',
            self::DENSITY => 'Density',
            self::CONCENTRATION => 'Concentration',
            self::BOOLEAN => 'Boolean',
            self::COUNTER => 'Counter',
            self::ENUM => 'Enumeration',
            self::TEXT => 'Text',
            self::BINARY => 'Binary',
            self::JSON => 'JSON',
        };
    }

    public function getCategory(): string
    {
        return match($this) {
            self::TEMPERATURE, self::HUMIDITY, self::PRESSURE => 'Environmental',
            self::VOLTAGE, self::CURRENT, self::POWER, self::ENERGY => 'Electrical',
            self::VIBRATION, self::MOTION, self::SPEED, self::ACCELERATION, self::ROTATION, self::TORQUE => 'Mechanical',
            self::LIGHT, self::SOUND, self::GAS, self::RADIATION => 'Sensing',
            self::FLOW, self::LEVEL, self::POSITION, self::DISTANCE, self::ANGLE, self::PROXIMITY => 'Positioning',
            self::FORCE, self::STRAIN, self::WEIGHT, self::MASS => 'Force',
            self::PH, self::CONDUCTIVITY, self::TURBIDITY, self::CONCENTRATION => 'Chemical',
            self::MAGNETIC => 'Magnetic',
            self::BOOLEAN, self::COUNTER, self::ENUM, self::TEXT, self::BINARY, self::JSON => 'Digital',
        };
    }

    public function getDefaultUnit(): string
    {
        return match($this) {
            self::TEMPERATURE => '°C',
            self::HUMIDITY => '%',
            self::PRESSURE => 'Pa',
            self::VOLTAGE => 'V',
            self::CURRENT => 'A',
            self::POWER => 'W',
            self::ENERGY => 'Wh',
            self::VIBRATION => 'Hz',
            self::MOTION => 'm/s',
            self::LIGHT => 'lux',
            self::SOUND => 'dB',
            self::FLOW => 'L/min',
            self::LEVEL => 'm',
            self::POSITION => 'm',
            self::SPEED => 'm/s',
            self::ACCELERATION => 'm/s²',
            self::ROTATION => 'rpm',
            self::TORQUE => 'Nm',
            self::FORCE => 'N',
            self::STRAIN => 'με',
            self::PH => 'pH',
            self::CONDUCTIVITY => 'S/m',
            self::TURBIDITY => 'NTU',
            self::GAS => 'ppm',
            self::RADIATION => 'Sv/h',
            self::MAGNETIC => 'T',
            self::PROXIMITY => 'm',
            self::DISTANCE => 'm',
            self::ANGLE => '°',
            self::WEIGHT => 'N',
            self::MASS => 'kg',
            self::VOLUME => 'L',
            self::DENSITY => 'kg/m³',
            self::CONCENTRATION => 'mg/L',
            self::BOOLEAN => 'bool',
            self::COUNTER => 'count',
            self::ENUM => 'enum',
            self::TEXT => 'text',
            self::BINARY => 'binary',
            self::JSON => 'json',
        };
    }

    public function isNumeric(): bool
    {
        return !in_array($this, [self::BOOLEAN, self::ENUM, self::TEXT, self::BINARY, self::JSON]);
    }

    public function requiresThreshold(): bool
    {
        return $this->isNumeric();
    }
}
