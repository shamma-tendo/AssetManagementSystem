<?php

namespace Database\Factories;

use App\Models\Sensor;
use App\Models\Asset;
use App\Models\SensorType;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<Sensor>
 */
class SensorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $asset = Asset::inRandomOrder()->first() ?? Asset::factory()->create();
        $sensorType = SensorType::inRandomOrder()->first() ?? SensorType::factory()->create();
        $creator = User::factory()->create(['role' => UserRole::MANAGER]);

        return [
            'asset_id' => $asset->id,
            'sensor_type_id' => $sensorType->id,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(3),
            'manufacturer' => $this->faker->randomElement(['Siemens', 'Honeywell', 'Schneider Electric', 'ABB', 'Emerson', 'Yokogawa', 'Endress+Hauser']),
            'model' => $this->faker->bothify('??-####'),
            'serial_number' => $this->faker->bothify('SN#########'),
            'firmware_version' => $this->faker->semver(),
            'hardware_version' => $this->faker->semver(),
            'mac_address' => $this->faker->macAddress(),
            'ip_address' => $this->faker->ipv4(),
            'location_description' => $this->faker->words(2, true),
            'installation_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'calibration_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'next_calibration_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'battery_level' => $this->faker->numberBetween(10, 100),
            'signal_strength' => $this->faker->numberBetween(20, 100),
            'status' => $this->faker->randomElement(['active', 'inactive', 'maintenance', 'error', 'calibrating', 'offline']),
            'configuration' => [
                'sampling_rate' => $this->faker->numberBetween(1, 60),
                'buffer_size' => $this->faker->numberBetween(100, 1000),
                'compression' => $this->faker->boolean(),
                'encryption' => $this->faker->boolean(),
            ],
            'threshold_min' => $this->faker->randomFloat(2, -50, 0),
            'threshold_max' => $this->faker->randomFloat(2, 0, 50),
            'alert_enabled' => $this->faker->boolean(80),
            'data_retention_days' => $this->faker->numberBetween(30, 365),
            'sampling_interval' => $this->faker->numberBetween(60, 3600),
            'last_data_received' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'last_heartbeat' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
            'notes' => $this->faker->boolean(40) ? $this->faker->sentence(3) : null,
            'created_by' => $creator->id,
        ];
    }

    /**
     * Create an active sensor.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'battery_level' => $this->faker->numberBetween(50, 100),
            'signal_strength' => $this->faker->numberBetween(60, 100),
            'last_heartbeat' => now()->subMinutes(rand(1, 15)),
            'last_data_received' => now()->subMinutes(rand(1, 10)),
        ]);
    }

    /**
     * Create an inactive sensor.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'last_heartbeat' => now()->subHours(rand(1, 24)),
            'last_data_received' => now()->subHours(rand(1, 24)),
        ]);
    }

    /**
     * Create a sensor in maintenance mode.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'maintenance',
            'last_heartbeat' => now()->subMinutes(rand(1, 30)),
            'notes' => 'Undergoing scheduled maintenance',
        ]);
    }

    /**
     * Create a sensor with error status.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
            'last_heartbeat' => now()->subMinutes(rand(1, 60)),
            'notes' => 'Sensor error detected',
        ]);
    }

    /**
     * Create a sensor that is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offline',
            'last_heartbeat' => now()->subHours(rand(1, 6)),
            'last_data_received' => now()->subHours(rand(1, 6)),
            'battery_level' => $this->faker->numberBetween(0, 30),
            'signal_strength' => $this->faker->numberBetween(0, 30),
        ]);
    }

    /**
     * Create a sensor with low battery.
     */
    public function lowBattery(): static
    {
        return $this->state(fn (array $attributes) => [
            'battery_level' => $this->faker->numberBetween(0, 20),
            'status' => 'active',
            'last_heartbeat' => now()->subMinutes(rand(1, 15)),
        ]);
    }

    /**
     * Create a sensor with poor signal.
     */
    public function poorSignal(): static
    {
        return $this->state(fn (array $attributes) => [
            'signal_strength' => $this->faker->numberBetween(0, 30),
            'status' => 'active',
            'last_heartbeat' => now()->subMinutes(rand(1, 15)),
        ]);
    }

    /**
     * Create a sensor that needs calibration.
     */
    public function needsCalibration(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_calibration_date' => now()->subDays(rand(1, 30)),
            'calibration_date' => now()->subMonths(rand(6, 12)),
            'status' => 'active',
        ]);
    }

    /**
     * Create a temperature sensor.
     */
    public function temperature(): static
    {
        return $this->state(fn (array $attributes) => [
            'sensor_type_id' => SensorType::where('data_type', 'temperature')->first()->id ?? SensorType::factory()->temperature()->create()->id,
            'name' => 'Temperature Sensor',
            'threshold_min' => 15.0,
            'threshold_max' => 35.0,
            'unit_of_measure' => '°C',
        ]);
    }

    /**
     * Create a humidity sensor.
     */
    public function humidity(): static
    {
        return $this->state(fn (array $attributes) => [
            'sensor_type_id' => SensorType::where('data_type', 'humidity')->first()->id ?? SensorType::factory()->humidity()->create()->id,
            'name' => 'Humidity Sensor',
            'threshold_min' => 30.0,
            'threshold_max' => 70.0,
            'unit_of_measure' => '%',
        ]);
    }

    /**
     * Create a pressure sensor.
     */
    public function pressure(): static
    {
        return $this->state(fn (array $attributes) => [
            'sensor_type_id' => SensorType::where('data_type', 'pressure')->first()->id ?? SensorType::factory()->pressure()->create()->id,
            'name' => 'Pressure Sensor',
            'threshold_min' => 80000.0,
            'threshold_max' => 120000.0,
            'unit_of_measure' => 'Pa',
        ]);
    }

    /**
     * Create a vibration sensor.
     */
    public function vibration(): static
    {
        return $this->state(fn (array $attributes) => [
            'sensor_type_id' => SensorType::where('data_type', 'vibration')->first()->id ?? SensorType::factory()->vibration()->create()->id,
            'name' => 'Vibration Sensor',
            'threshold_max' => 500.0,
            'unit_of_measure' => 'Hz',
        ]);
    }

    /**
     * Create a motion sensor.
     */
    public function motion(): static
    {
        return $this->state(fn (array $attributes) => [
            'sensor_type_id' => SensorType::where('data_type', 'motion')->first()->id ?? SensorType::factory()->motion()->create()->id,
            'name' => 'Motion Sensor',
            'threshold_max' => 2.0,
            'unit_of_measure' => 'm/s',
        ]);
    }

    /**
     * Create a sensor with alerts enabled.
     */
    public function withAlerts(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_enabled' => true,
            'threshold_min' => $this->faker->randomFloat(2, -30, -10),
            'threshold_max' => $this->faker->randomFloat(2, 10, 30),
        ]);
    }

    /**
     * Create a sensor with alerts disabled.
     */
    public function withoutAlerts(): static
    {
        return $this->state(fn (array $attributes) => [
            'alert_enabled' => false,
        ]);
    }

    /**
     * Create a sensor with high sampling rate.
     */
    public function highSamplingRate(): static
    {
        return $this->state(fn (array $attributes) => [
            'sampling_interval' => $this->faker->numberBetween(1, 60),
            'configuration' => array_merge($attributes['configuration'] ?? [], [
                'sampling_rate' => $this->faker->numberBetween(1, 10),
            ]),
        ]);
    }

    /**
     * Create a sensor with low sampling rate.
     */
    public function lowSamplingRate(): static
    {
        return $this->state(fn (array $attributes) => [
            'sampling_interval' => $this->faker->numberBetween(1800, 7200),
            'configuration' => array_merge($attributes['configuration'] ?? [], [
                'sampling_rate' => $this->faker->numberBetween(30, 120),
            ]),
        ]);
    }

    /**
     * Create a newly installed sensor.
     */
    public function newlyInstalled(): static
    {
        return $this->state(fn (array $attributes) => [
            'installation_date' => now()->subDays(rand(1, 7)),
            'calibration_date' => now()->subDays(rand(1, 7)),
            'next_calibration_date' => now()->addMonths(6),
            'status' => 'active',
            'last_heartbeat' => now()->subMinutes(rand(1, 5)),
            'last_data_received' => now()->subMinutes(rand(1, 3)),
        ]);
    }

    /**
     * Create an old sensor.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'installation_date' => now()->subYears(rand(3, 10)),
            'calibration_date' => now()->subMonths(rand(12, 36)),
            'next_calibration_date' => now()->subMonths(rand(1, 6)),
            'firmware_version' => '1.' . $this->faker->numberBetween(0, 5) . '.' . $this->faker->numberBetween(0, 9),
            'battery_level' => $this->faker->numberBetween(20, 60),
            'signal_strength' => $this->faker->numberBetween(40, 80),
        ]);
    }

    /**
     * Create a sensor for specific asset.
     */
    public function forAsset(Asset $asset): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_id' => $asset->id,
        ]);
    }

    /**
     * Create a sensor of specific type.
     */
    public function ofType(SensorType $sensorType): static
    {
        return $this->state(fn (array $attributes) => [
            'sensor_type_id' => $sensorType->id,
        ]);
    }

    /**
     * Create a sensor with specific status.
     */
    public function withStatus(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Create a sensor for server room monitoring.
     */
    public function serverRoom(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_description' => $this->faker->randomElement(['Server Room Rack A1', 'Server Room Rack B2', 'Server Room Rack C3', 'Server Room Rack D4']),
            'sensor_type_id' => SensorType::where('data_type', 'temperature')->first()->id ?? SensorType::factory()->temperature()->create()->id,
            'name' => 'Server Room Temperature Sensor',
            'threshold_min' => 18.0,
            'threshold_max' => 28.0,
            'alert_enabled' => true,
        ]);
    }

    /**
     * Create a sensor for industrial monitoring.
     */
    public function industrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'manufacturer' => $this->faker->randomElement(['Siemens', 'Honeywell', 'Schneider Electric', 'ABB']),
            'model' => $this->faker->bothify('IND-####-##'),
            'sensor_type_id' => SensorType::where('data_type', 'pressure')->first()->id ?? SensorType::factory()->pressure()->create()->id,
            'name' => 'Industrial Pressure Sensor',
            'threshold_min' => 90000.0,
            'threshold_max' => 110000.0,
            'alert_enabled' => true,
            'data_retention_days' => 365,
        ]);
    }

    /**
     * Create a wireless sensor.
     */
    public function wireless(): static
    {
        return $this->state(fn (array $attributes) => [
            'communication_protocol' => $this->faker->randomElement(['WiFi', 'Bluetooth', 'LoRaWAN', 'Zigbee']),
            'power_requirements' => 'Battery Powered',
            'battery_level' => $this->faker->numberBetween(30, 90),
            'signal_strength' => $this->faker->numberBetween(40, 85),
            'configuration' => array_merge($attributes['configuration'] ?? [], [
                'wireless' => true,
                'encryption' => true,
            ]),
        ]);
    }

    /**
     * Create a wired sensor.
     */
    public function wired(): static
    {
        return $this->state(fn (array $attributes) => [
            'communication_protocol' => $this->faker->randomElement(['MQTT', 'HTTP', 'CoAP']),
            'power_requirements' => $this->faker->randomElement(['12V DC', '24V DC']),
            'battery_level' => null,
            'signal_strength' => null,
            'configuration' => array_merge($attributes['configuration'] ?? [], [
                'wireless' => false,
            ]),
        ]);
    }
}
