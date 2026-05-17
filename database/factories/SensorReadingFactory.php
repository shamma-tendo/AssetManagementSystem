<?php

namespace Database\Factories;

use App\Models\Sensor;
use App\Models\SensorReading;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<SensorReading>
 */
class SensorReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sensor = Sensor::inRandomOrder()->first() ?? Sensor::factory()->create();
        
        // Generate realistic value based on sensor type
        $value = $this->generateValueForSensorType($sensor->sensorType->data_type);
        
        return [
            'sensor_id' => $sensor->id,
            'timestamp' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'value' => $value,
            'unit' => $sensor->sensorType->unit_of_measure,
            'quality' => $this->faker->randomFloat(4, 0.5, 1.0),
            'raw_data' => $this->faker->boolean(30) ? [
                'raw_adc_value' => $this->faker->numberBetween(0, 4095),
                'calibration_offset' => $this->faker->randomFloat(4, -1, 1),
                'temperature_compensation' => $this->faker->randomFloat(4, -0.5, 0.5),
            ] : null,
            'processed_data' => $this->faker->boolean(40) ? [
                'filtered_value' => $value + $this->faker->randomFloat(4, -0.1, 0.1),
                'moving_average' => $value + $this->faker->randomFloat(4, -0.05, 0.05),
                'trend' => $this->faker->randomElement(['increasing', 'decreasing', 'stable']),
            ] : null,
            'metadata' => $this->faker->boolean(50) ? [
                'device_id' => $this->faker->uuid(),
                'firmware_version' => $this->faker->semver(),
                'gateway_id' => $this->faker->bothify('GW-####'),
            ] : null,
            'battery_level' => $sensor->battery_level ? $this->faker->numberBetween($sensor->battery_level - 5, $sensor->battery_level + 5) : null,
            'signal_strength' => $sensor->signal_strength ? $this->faker->numberBetween($sensor->signal_strength - 10, $sensor->signal_strength + 10) : null,
            'temperature' => $this->faker->randomFloat(2, 15, 35),
            'humidity' => $this->faker->randomFloat(2, 30, 70),
            'error_code' => $this->faker->boolean(5) ? $this->faker->numberBetween(100, 999) : null,
            'status_flags' => $this->faker->boolean(10) ? [
                'calibration_pending' => $this->faker->boolean(),
                'maintenance_required' => $this->faker->boolean(),
                'data_quality_warning' => $this->faker->boolean(),
            ] : null,
        ];
    }

    /**
     * Generate realistic value based on sensor type.
     */
    private function generateValueForSensorType(string $dataType): float
    {
        return match($dataType) {
            'temperature' => $this->faker->randomFloat(2, 15, 35),
            'humidity' => $this->faker->randomFloat(2, 30, 70),
            'pressure' => $this->faker->randomFloat(2, 90000, 110000),
            'voltage' => $this->faker->randomFloat(2, 0, 480),
            'current' => $this->faker->randomFloat(2, 0, 100),
            'power' => $this->faker->randomFloat(2, 0, 10000),
            'energy' => $this->faker->randomFloat(2, 0, 1000000),
            'vibration' => $this->faker->randomFloat(2, 0, 1000),
            'motion' => $this->faker->randomFloat(2, 0, 10),
            'light' => $this->faker->randomFloat(2, 0, 50000),
            'sound' => $this->faker->randomFloat(2, 30, 120),
            'flow' => $this->faker->randomFloat(2, 0, 1000),
            'level' => $this->faker->randomFloat(2, 0, 10),
            'position' => $this->faker->randomFloat(2, -100, 100),
            'speed' => $this->faker->randomFloat(2, 0, 100),
            'acceleration' => $this->faker->randomFloat(2, -50, 50),
            'rotation' => $this->faker->randomFloat(2, 0, 3000),
            'torque' => $this->faker->randomFloat(2, 0, 1000),
            'force' => $this->faker->randomFloat(2, 0, 10000),
            'strain' => $this->faker->randomFloat(2, -1000, 1000),
            'ph' => $this->faker->randomFloat(2, 0, 14),
            'conductivity' => $this->faker->randomFloat(2, 0, 100),
            'turbidity' => $this->faker->randomFloat(2, 0, 1000),
            'gas' => $this->faker->randomFloat(2, 0, 10000),
            'radiation' => $this->faker->randomFloat(2, 0, 100),
            'magnetic' => $this->faker->randomFloat(2, -1, 1),
            'proximity' => $this->faker->randomFloat(2, 0, 10),
            'distance' => $this->faker->randomFloat(2, 0, 100),
            'angle' => $this->faker->randomFloat(2, -180, 180),
            'weight' => $this->faker->randomFloat(2, 0, 1000),
            'mass' => $this->faker->randomFloat(2, 0, 1000),
            'volume' => $this->faker->randomFloat(2, 0, 1000),
            'density' => $this->faker->randomFloat(2, 0, 10000),
            'concentration' => $this->faker->randomFloat(2, 0, 1000),
            'boolean' => $this->faker->boolean(),
            'counter' => $this->faker->numberBetween(0, 1000000),
            'enum' => $this->faker->numberBetween(1, 5),
            'text' => 1, // Placeholder for text
            'binary' => $this->faker->numberBetween(0, 1),
            'json' => 1, // Placeholder for JSON
            default => $this->faker->randomFloat(2, 0, 100),
        };
    }

    /**
     * Create a recent reading.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'timestamp' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'quality' => $this->faker->randomFloat(4, 0.8, 1.0),
        ]);
    }

    /**
     * Create an old reading.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'timestamp' => $this->faker->dateTimeBetween('-30 days', '-7 days'),
        ]);
    }

    /**
     * Create a reading with high quality.
     */
    public function highQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'quality' => $this->faker->randomFloat(4, 0.9, 1.0),
            'error_code' => null,
        ]);
    }

    /**
     * Create a reading with poor quality.
     */
    public function poorQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'quality' => $this->faker->randomFloat(4, 0.1, 0.5),
            'error_code' => $this->faker->boolean(30) ? $this->faker->numberBetween(100, 999) : null,
        ]);
    }

    /**
     * Create a reading with errors.
     */
    public function withErrors(): static
    {
        return $this->state(fn (array $attributes) => [
            'quality' => $this->faker->randomFloat(4, 0, 0.3),
            'error_code' => $this->faker->numberBetween(100, 999),
            'status_flags' => [
                'sensor_error' => true,
                'communication_error' => $this->faker->boolean(),
                'calibration_error' => $this->faker->boolean(),
            ],
        ]);
    }

    /**
     * Create a reading for a specific sensor.
     */
    public function forSensor(Sensor $sensor): static
    {
        return $this->state(fn (array $attributes) => [
            'sensor_id' => $sensor->id,
            'unit' => $sensor->sensorType->unit_of_measure,
            'value' => $this->generateValueForSensorType($sensor->sensorType->data_type),
        ]);
    }

    /**
     * Create a reading for specific time period.
     */
    public function forPeriod(Carbon $timestamp): static
    {
        return $this->state(fn (array $attributes) => [
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * Create a reading that exceeds thresholds.
     */
    public function exceedsThresholds(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                if (!$sensor) {
                    return $this->faker->randomFloat(2, 0, 100);
                }

                $threshold = $this->faker->boolean() ? $sensor->threshold_max : $sensor->threshold_min;
                if ($threshold === null) {
                    return $this->faker->randomFloat(2, 0, 100);
                }

                // Generate value 20-50% beyond threshold
                $multiplier = $this->faker->randomFloat(2, 1.2, 1.5);
                return $threshold * $multiplier;
            },
        ]);
    }

    /**
     * Create a reading below minimum threshold.
     */
    public function belowThreshold(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                if (!$sensor || $sensor->threshold_min === null) {
                    return $this->faker->randomFloat(2, 0, 100);
                }

                // Generate value 20-50% below threshold
                $multiplier = $this->faker->randomFloat(2, 0.5, 0.8);
                return $sensor->threshold_min * $multiplier;
            },
        ]);
    }

    /**
     * Create a reading above maximum threshold.
     */
    public function aboveThreshold(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                if (!$sensor || $sensor->threshold_max === null) {
                    return $this->faker->randomFloat(2, 0, 100);
                }

                // Generate value 20-50% above threshold
                $multiplier = $this->faker->randomFloat(2, 1.2, 1.5);
                return $sensor->threshold_max * $multiplier;
            },
        ]);
    }

    /**
     * Create an anomalous reading.
     */
    public function anomalous(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                if (!$sensor) {
                    return $this->faker->randomFloat(2, 0, 100);
                }

                // Get recent readings to establish baseline
                $recentReadings = $sensor->readings()
                    ->where('timestamp', '>=', now()->subHours(24))
                    ->where('quality', '>=', 0.8)
                    ->limit(20)
                    ->pluck('value');

                if ($recentReadings->count() < 5) {
                    return $this->faker->randomFloat(2, 0, 100);
                }

                $mean = $recentReadings->avg();
                $stdDev = $this->calculateStandardDeviation($recentReadings->toArray());
                
                if ($stdDev == 0) {
                    return $mean + $this->faker->randomFloat(2, 10, 50);
                }

                // Generate value 3-5 standard deviations from mean
                $zScore = $this->faker->randomFloat(2, 3, 5);
                $direction = $this->faker->boolean() ? 1 : -1;
                
                return $mean + ($direction * $zScore * $stdDev);
            },
        ]);
    }

    /**
     * Create a reading with low battery.
     */
    public function lowBattery(): static
    {
        return $this->state(fn (array $attributes) => [
            'battery_level' => $this->faker->numberBetween(0, 20),
        ]);
    }

    /**
     * Create a reading with poor signal.
     */
    public function poorSignal(): static
    {
        return $this->state(fn (array $attributes) => [
            'signal_strength' => $this->faker->numberBetween(0, 30),
        ]);
    }

    /**
     * Create a temperature reading.
     */
    public function temperature(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $this->faker->randomFloat(2, 15, 35),
            'unit' => '°C',
        ]);
    }

    /**
     * Create a humidity reading.
     */
    public function humidity(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $this->faker->randomFloat(2, 30, 70),
            'unit' => '%',
        ]);
    }

    /**
     * Create a pressure reading.
     */
    public function pressure(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $this->faker->randomFloat(2, 90000, 110000),
            'unit' => 'Pa',
        ]);
    }

    /**
     * Create a vibration reading.
     */
    public function vibration(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $this->faker->randomFloat(2, 0, 1000),
            'unit' => 'Hz',
        ]);
    }

    /**
     * Create a reading with metadata.
     */
    public function withMetadata(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'device_id' => $this->faker->uuid(),
                'firmware_version' => $this->faker->semver(),
                'gateway_id' => $this->faker->bothify('GW-####'),
                'network_id' => $this->faker->bothify('NET-####'),
                'location_coordinates' => [
                    'latitude' => $this->faker->latitude,
                    'longitude' => $this->faker->longitude,
                ],
                'environmental_conditions' => [
                    'ambient_temperature' => $this->faker->randomFloat(2, 15, 35),
                    'ambient_humidity' => $this->faker->randomFloat(2, 30, 70),
                    'atmospheric_pressure' => $this->faker->randomFloat(2, 98000, 105000),
                ],
            ],
        ]);
    }

    /**
     * Create a reading with raw data.
     */
    public function withRawData(): static
    {
        return $this->state(fn (array $attributes) => [
            'raw_data' => [
                'raw_adc_value' => $this->faker->numberBetween(0, 4095),
                'calibration_offset' => $this->faker->randomFloat(4, -1, 1),
                'temperature_compensation' => $this->faker->randomFloat(4, -0.5, 0.5),
                'humidity_compensation' => $this->faker->randomFloat(4, -0.3, 0.3),
                'pressure_compensation' => $this->faker->randomFloat(4, -100, 100),
                'noise_level' => $this->faker->randomFloat(4, 0, 0.1),
                'signal_strength_raw' => $this->faker->numberBetween(-100, 0),
                'battery_voltage_raw' => $this->faker->randomFloat(4, 2.5, 3.3),
            ],
        ]);
    }

    /**
     * Create a reading with processed data.
     */
    public function withProcessedData(): static
    {
        return $this->state(fn (array $attributes) => [
            'processed_data' => [
                'filtered_value' => $attributes['value'] + $this->faker->randomFloat(4, -0.1, 0.1),
                'moving_average' => $attributes['value'] + $this->faker->randomFloat(4, -0.05, 0.05),
                'trend' => $this->faker->randomElement(['increasing', 'decreasing', 'stable']),
                'confidence_score' => $this->faker->randomFloat(4, 0.7, 1.0),
                'prediction' => $attributes['value'] + $this->faker->randomFloat(4, -1, 1),
                'anomaly_score' => $this->faker->randomFloat(4, 0, 1),
                'health_indicator' => $this->faker->randomElement(['excellent', 'good', 'fair', 'poor']),
            ],
        ]);
    }

    /**
     * Create a reading for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'timestamp' => $this->faker->dateTimeBetween('today', 'now'),
        ]);
    }

    /**
     * Create a reading for this week.
     */
    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'timestamp' => $this->faker->dateTimeBetween('this week', 'now'),
        ]);
    }

    /**
     * Create a reading for this month.
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'timestamp' => $this->faker->dateTimeBetween('this month', 'now'),
        ]);
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
}
