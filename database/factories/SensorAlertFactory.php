<?php

namespace Database\Factories;

use App\Models\Sensor;
use App\Models\SensorAlert;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<SensorAlert>
 */
class SensorAlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sensor = Sensor::inRandomOrder()->first() ?? Sensor::factory()->create();
        $alertType = $this->faker->randomElement(['threshold_high', 'threshold_low', 'anomaly', 'quality', 'offline', 'low_battery', 'poor_signal', 'calibration_due', 'maintenance_due', 'communication_error', 'sensor_error', 'data_gap', 'system_error']);
        $severity = $this->getSeverityForAlertType($alertType);

        return [
            'sensor_id' => $sensor->id,
            'alert_type' => $alertType,
            'severity' => $severity,
            'message' => $this->generateMessageForAlertType($alertType, $sensor),
            'description' => $this->faker->boolean(60) ? $this->faker->sentence(3) : null,
            'trigger_value' => $this->generateTriggerValue($alertType, $sensor),
            'threshold_value' => $this->generateThresholdValue($alertType, $sensor),
            'triggered_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'acknowledged_at' => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
            'acknowledged_by' => $this->faker->boolean(40) ? $this->faker->uuid() : null,
            'resolved_at' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
            'resolved_by' => $this->faker->boolean(30) ? $this->faker->uuid() : null,
            'resolution_notes' => $this->faker->boolean(50) ? $this->faker->sentence(3) : null,
            'metadata' => $this->faker->boolean(40) ? [
                'gateway_id' => $this->faker->bothify('GW-####'),
                'network_id' => $this->faker->bothify('NET-####'),
                'device_health' => $this->faker->randomElement(['good', 'fair', 'poor']),
            ] : null,
            'auto_resolved' => $this->faker->boolean(20),
            'escalation_level' => $this->faker->numberBetween(0, 3),
            'notification_sent' => $this->faker->boolean(70),
        ];
    }

    /**
     * Get severity for alert type.
     */
    private function getSeverityForAlertType(string $alertType): string
    {
        return match($alertType) {
            'threshold_high', 'threshold_low' => $this->faker->randomElement(['low', 'medium', 'high']),
            'anomaly' => $this->faker->randomElement(['info', 'low', 'medium']),
            'quality' => $this->faker->randomElement(['low', 'medium']),
            'offline' => $this->faker->randomElement(['medium', 'high', 'critical']),
            'low_battery' => $this->faker->randomElement(['medium', 'high']),
            'poor_signal' => $this->faker->randomElement(['low', 'medium']),
            'calibration_due' => $this->faker->randomElement(['low', 'medium']),
            'maintenance_due' => $this->faker->randomElement(['medium', 'high']),
            'communication_error' => $this->faker->randomElement(['medium', 'high', 'critical']),
            'sensor_error' => $this->faker->randomElement(['high', 'critical']),
            'data_gap' => $this->faker->randomElement(['low', 'medium']),
            'system_error' => $this->faker->randomElement(['high', 'critical']),
            default => 'medium',
        };
    }

    /**
     * Generate message for alert type.
     */
    private function generateMessageForAlertType(string $alertType, Sensor $sensor): string
    {
        return match($alertType) {
            'threshold_high' => "Sensor {$sensor->name} value exceeds maximum threshold",
            'threshold_low' => "Sensor {$sensor->name} value below minimum threshold",
            'anomaly' => "Anomalous reading detected from sensor {$sensor->name}",
            'quality' => "Poor data quality from sensor {$sensor->name}",
            'offline' => "Sensor {$sensor->name} is offline",
            'low_battery' => "Low battery detected in sensor {$sensor->name}",
            'poor_signal' => "Poor signal strength from sensor {$sensor->name}",
            'calibration_due' => "Calibration due for sensor {$sensor->name}",
            'maintenance_due' => "Maintenance required for sensor {$sensor->name}",
            'communication_error' => "Communication error with sensor {$sensor->name}",
            'sensor_error' => "Sensor error detected in {$sensor->name}",
            'data_gap' => "Data gap detected for sensor {$sensor->name}",
            'system_error' => "System error affecting sensor {$sensor->name}",
            default => "Alert generated for sensor {$sensor->name}",
        };
    }

    /**
     * Generate trigger value for alert type.
     */
    private function generateTriggerValue(string $alertType, Sensor $sensor): ?float
    {
        return match($alertType) {
            'threshold_high' => $sensor->threshold_max ? $sensor->threshold_max + $this->faker->randomFloat(2, 1, 10) : null,
            'threshold_low' => $sensor->threshold_min ? $sensor->threshold_min - $this->faker->randomFloat(2, 1, 10) : null,
            'anomaly' => $this->faker->randomFloat(2, 0, 100),
            'quality' => $this->faker->randomFloat(4, 0, 0.5),
            'low_battery' => $this->faker->numberBetween(0, 20),
            'poor_signal' => $this->faker->numberBetween(0, 30),
            default => null,
        };
    }

    /**
     * Generate threshold value for alert type.
     */
    private function generateThresholdValue(string $alertType, Sensor $sensor): ?float
    {
        return match($alertType) {
            'threshold_high' => $sensor->threshold_max,
            'threshold_low' => $sensor->threshold_min,
            'quality' => 0.5,
            'low_battery' => 20,
            'poor_signal' => 30,
            default => null,
        };
    }

    /**
     * Create an unacknowledged alert.
     */
    public function unacknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'acknowledged_at' => null,
            'acknowledged_by' => null,
        ]);
    }

    /**
     * Create an unresolved alert.
     */
    public function unresolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => null,
            'resolved_by' => null,
        ]);
    }

    /**
     * Create an active alert (unacknowledged and unresolved).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'acknowledged_at' => null,
            'acknowledged_by' => null,
            'resolved_at' => null,
            'resolved_by' => null,
        ]);
    }

    /**
     * Create an acknowledged alert.
     */
    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'acknowledged_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'acknowledged_by' => $this->faker->uuid(),
        ]);
    }

    /**
     * Create a resolved alert.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'resolved_by' => $this->faker->uuid(),
            'resolution_notes' => $this->faker->sentence(3),
        ]);
    }

    /**
     * Create a critical alert.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
            'alert_type' => $this->faker->randomElement(['offline', 'sensor_error', 'system_error']),
            'escalation_level' => $this->faker->numberBetween(2, 3),
            'notification_sent' => true,
        ]);
    }

    /**
     * Create a high severity alert.
     */
    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'high',
            'alert_type' => $this->faker->randomElement(['threshold_high', 'threshold_low', 'communication_error', 'low_battery']),
            'escalation_level' => $this->faker->numberBetween(1, 2),
            'notification_sent' => true,
        ]);
    }

    /**
     * Create a medium severity alert.
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'medium',
            'alert_type' => $this->faker->randomElement(['threshold_high', 'threshold_low', 'anomaly', 'quality', 'maintenance_due']),
            'escalation_level' => $this->faker->numberBetween(0, 1),
            'notification_sent' => $this->faker->boolean(80),
        ]);
    }

    /**
     * Create a low severity alert.
     */
    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'low',
            'alert_type' => $this->faker->randomElement(['anomaly', 'poor_signal', 'data_gap', 'calibration_due']),
            'escalation_level' => 0,
            'notification_sent' => $this->faker->boolean(60),
        ]);
    }

    /**
     * Create an info severity alert.
     */
    public function info(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'info',
            'alert_type' => 'anomaly',
            'escalation_level' => 0,
            'notification_sent' => $this->faker->boolean(40),
        ]);
    }

    /**
     * Create a threshold high alert.
     */
    public function thresholdHigh(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'threshold_high',
            'message' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return "Sensor {$sensor->name} value exceeds maximum threshold";
            },
            'trigger_value' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return $sensor->threshold_max + $this->faker->randomFloat(2, 1, 10);
            },
            'threshold_value' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return $sensor->threshold_max;
            },
        ]);
    }

    /**
     * Create a threshold low alert.
     */
    public function thresholdLow(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'threshold_low',
            'message' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return "Sensor {$sensor->name} value below minimum threshold";
            },
            'trigger_value' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return $sensor->threshold_min - $this->faker->randomFloat(2, 1, 10);
            },
            'threshold_value' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return $sensor->threshold_min;
            },
        ]);
    }

    /**
     * Create an anomaly alert.
     */
    public function anomaly(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'anomaly',
            'severity' => $this->faker->randomElement(['info', 'low', 'medium']),
            'trigger_value' => $this->faker->randomFloat(2, 0, 100),
            'message' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return "Anomalous reading detected from sensor {$sensor->name}";
            },
        ]);
    }

    /**
     * Create a quality alert.
     */
    public function quality(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'quality',
            'severity' => $this->faker->randomElement(['low', 'medium']),
            'trigger_value' => $this->faker->randomFloat(4, 0, 0.5),
            'threshold_value' => 0.5,
            'message' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return "Poor data quality from sensor {$sensor->name}";
            },
        ]);
    }

    /**
     * Create an offline alert.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'offline',
            'severity' => $this->faker->randomElement(['medium', 'high', 'critical']),
            'escalation_level' => $this->faker->numberBetween(1, 3),
            'notification_sent' => true,
            'message' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return "Sensor {$sensor->name} is offline";
            },
        ]);
    }

    /**
     * Create a low battery alert.
     */
    public function lowBattery(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'low_battery',
            'severity' => $this->faker->randomElement(['medium', 'high']),
            'escalation_level' => $this->faker->numberBetween(1, 2),
            'trigger_value' => $this->faker->numberBetween(0, 20),
            'threshold_value' => 20,
            'message' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return "Low battery detected in sensor {$sensor->name}";
            },
        ]);
    }

    /**
     * Create a poor signal alert.
     */
    public function poorSignal(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'poor_signal',
            'severity' => $this->faker->randomElement(['low', 'medium']),
            'trigger_value' => $this->faker->numberBetween(0, 30),
            'threshold_value' => 30,
            'message' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return "Poor signal strength from sensor {$sensor->name}";
            },
        ]);
    }

    /**
     * Create a calibration due alert.
     */
    public function calibrationDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'calibration_due',
            'severity' => $this->faker->randomElement(['low', 'medium']),
            'message' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return "Calibration due for sensor {$sensor->name}";
            },
        ]);
    }

    /**
     * Create a maintenance due alert.
     */
    public function maintenanceDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'maintenance_due',
            'severity' => $this->faker->randomElement(['medium', 'high']),
            'message' => function (array $attributes) {
                $sensor = Sensor::find($attributes['sensor_id']);
                return "Maintenance required for sensor {$sensor->name}";
            },
        ]);
    }

    /**
     * Create a recent alert.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'triggered_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Create an old alert.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'triggered_at' => $this->faker->dateTimeBetween('-30 days', '-7 days'),
        ]);
    }

    /**
     * Create an auto-resolved alert.
     */
    public function autoResolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_resolved' => true,
            'resolved_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'resolution_notes' => 'Automatically resolved',
        ]);
    }

    /**
     * Create an escalated alert.
     */
    public function escalated(): static
    {
        return $this->state(fn (array $attributes) => [
            'escalation_level' => $this->faker->numberBetween(2, 3),
            'severity' => $this->faker->randomElement(['high', 'critical']),
            'notification_sent' => true,
        ]);
    }

    /**
     * Create an alert for specific sensor.
     */
    public function forSensor(Sensor $sensor): static
    {
        return $this->state(fn (array $attributes) => [
            'sensor_id' => $sensor->id,
        ]);
    }

    /**
     * Create an alert with metadata.
     */
    public function withMetadata(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'gateway_id' => $this->faker->bothify('GW-####'),
                'network_id' => $this->faker->bothify('NET-####'),
                'device_health' => $this->faker->randomElement(['good', 'fair', 'poor']),
                'last_communication' => $this->faker->dateTimeBetween('-1 hour', 'now')->format('Y-m-d H:i:s'),
                'retry_count' => $this->faker->numberBetween(0, 5),
                'error_details' => $this->faker->boolean(30) ? [
                    'error_code' => $this->faker->numberBetween(100, 999),
                    'error_message' => $this->faker->sentence(3),
                    'stack_trace' => $this->faker->boolean(50) ? $this->faker->text(200) : null,
                ] : null,
            ],
        ]);
    }

    /**
     * Create an alert for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'triggered_at' => $this->faker->dateTimeBetween('today', 'now'),
        ]);
    }

    /**
     * Create an alert for this week.
     */
    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'triggered_at' => $this->faker->dateTimeBetween('this week', 'now'),
        ]);
    }

    /**
     * Create an alert that requires immediate attention.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
            'alert_type' => $this->faker->randomElement(['offline', 'sensor_error', 'system_error']),
            'escalation_level' => 3,
            'notification_sent' => true,
            'triggered_at' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
        ]);
    }
}
