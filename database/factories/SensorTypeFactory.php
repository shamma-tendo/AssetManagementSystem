<?php

namespace Database\Factories;

use App\Models\SensorType;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SensorType>
 */
class SensorTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dataTypes = [
            'temperature', 'humidity', 'pressure', 'voltage', 'current', 'power', 'energy',
            'vibration', 'motion', 'light', 'sound', 'flow', 'level', 'position', 'speed',
            'acceleration', 'rotation', 'torque', 'force', 'strain', 'ph', 'conductivity',
            'turbidity', 'gas', 'radiation', 'magnetic', 'proximity', 'distance', 'angle',
            'weight', 'mass', 'volume', 'density', 'concentration', 'boolean', 'counter',
            'enum', 'text', 'binary', 'json'
        ];

        $dataType = $this->faker->randomElement($dataTypes);
        $category = $this->getCategoryForDataType($dataType);
        $unit = $this->getUnitForDataType($dataType);

        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(3),
            'category' => $category,
            'unit_of_measure' => $unit,
            'data_type' => $dataType,
            'min_value' => $this->faker->randomFloat(2, -100, -10),
            'max_value' => $this->faker->randomFloat(2, 10, 100),
            'default_threshold_min' => $this->faker->randomFloat(2, -50, 0),
            'default_threshold_max' => $this->faker->randomFloat(2, 0, 50),
            'sampling_frequency' => $this->faker->numberBetween(1, 3600),
            'communication_protocol' => $this->faker->randomElement(['MQTT', 'HTTP', 'CoAP', 'LoRaWAN', 'Zigbee', 'Bluetooth', 'WiFi']),
            'power_requirements' => $this->faker->randomElement(['3.3V DC', '5V DC', '12V DC', '24V DC', 'Battery Powered']),
            'environmental_specs' => [
                'operating_temperature' => $this->faker->randomElement(['-40°C to 85°C', '-20°C to 70°C', '0°C to 50°C']),
                'operating_humidity' => $this->faker->randomElement(['10% to 90% RH', '20% to 80% RH', '5% to 95% RH']),
                'ip_rating' => $this->faker->randomElement(['IP20', 'IP65', 'IP67', 'IP68']),
            ],
            'accuracy' => $this->faker->randomFloat(4, 0.001, 0.1),
            'resolution' => $this->faker->randomFloat(6, 0.000001, 0.01),
            'response_time' => $this->faker->numberBetween(1, 1000),
            'is_active' => true,
            'created_by' => User::factory()->create(['role' => UserRole::MANAGER]),
        ];
    }

    /**
     * Get category for data type.
     */
    private function getCategoryForDataType(string $dataType): string
    {
        $categories = [
            'temperature' => 'Environmental',
            'humidity' => 'Environmental',
            'pressure' => 'Environmental',
            'voltage' => 'Electrical',
            'current' => 'Electrical',
            'power' => 'Electrical',
            'energy' => 'Electrical',
            'vibration' => 'Mechanical',
            'motion' => 'Mechanical',
            'speed' => 'Mechanical',
            'acceleration' => 'Mechanical',
            'rotation' => 'Mechanical',
            'torque' => 'Mechanical',
            'force' => 'Force',
            'strain' => 'Force',
            'light' => 'Sensing',
            'sound' => 'Sensing',
            'gas' => 'Sensing',
            'radiation' => 'Sensing',
            'flow' => 'Positioning',
            'level' => 'Positioning',
            'position' => 'Positioning',
            'distance' => 'Positioning',
            'angle' => 'Positioning',
            'proximity' => 'Positioning',
            'ph' => 'Chemical',
            'conductivity' => 'Chemical',
            'turbidity' => 'Chemical',
            'concentration' => 'Chemical',
            'magnetic' => 'Magnetic',
            'weight' => 'Force',
            'mass' => 'Force',
            'volume' => 'Positioning',
            'density' => 'Chemical',
            'boolean' => 'Digital',
            'counter' => 'Digital',
            'enum' => 'Digital',
            'text' => 'Digital',
            'binary' => 'Digital',
            'json' => 'Digital',
        ];

        return $categories[$dataType] ?? 'Other';
    }

    /**
     * Get unit for data type.
     */
    private function getUnitForDataType(string $dataType): string
    {
        $units = [
            'temperature' => '°C',
            'humidity' => '%',
            'pressure' => 'Pa',
            'voltage' => 'V',
            'current' => 'A',
            'power' => 'W',
            'energy' => 'Wh',
            'vibration' => 'Hz',
            'motion' => 'm/s',
            'speed' => 'm/s',
            'acceleration' => 'm/s²',
            'rotation' => 'rpm',
            'torque' => 'Nm',
            'force' => 'N',
            'strain' => 'με',
            'light' => 'lux',
            'sound' => 'dB',
            'gas' => 'ppm',
            'radiation' => 'Sv/h',
            'flow' => 'L/min',
            'level' => 'm',
            'position' => 'm',
            'distance' => 'm',
            'angle' => '°',
            'proximity' => 'm',
            'ph' => 'pH',
            'conductivity' => 'S/m',
            'turbidity' => 'NTU',
            'concentration' => 'mg/L',
            'magnetic' => 'T',
            'weight' => 'N',
            'mass' => 'kg',
            'volume' => 'L',
            'density' => 'kg/m³',
            'boolean' => 'bool',
            'counter' => 'count',
            'enum' => 'enum',
            'text' => 'text',
            'binary' => 'binary',
            'json' => 'json',
        ];

        return $units[$dataType] ?? 'unit';
    }

    /**
     * Create a temperature sensor type.
     */
    public function temperature(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Temperature Sensor',
            'data_type' => 'temperature',
            'category' => 'Environmental',
            'unit_of_measure' => '°C',
            'min_value' => -40,
            'max_value' => 85,
            'default_threshold_min' => 10,
            'default_threshold_max' => 35,
            'accuracy' => 0.1,
        ]);
    }

    /**
     * Create a humidity sensor type.
     */
    public function humidity(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Humidity Sensor',
            'data_type' => 'humidity',
            'category' => 'Environmental',
            'unit_of_measure' => '%',
            'min_value' => 0,
            'max_value' => 100,
            'default_threshold_min' => 30,
            'default_threshold_max' => 70,
            'accuracy' => 2.0,
        ]);
    }

    /**
     * Create a pressure sensor type.
     */
    public function pressure(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pressure Sensor',
            'data_type' => 'pressure',
            'category' => 'Environmental',
            'unit_of_measure' => 'Pa',
            'min_value' => 50000,
            'max_value' => 150000,
            'default_threshold_min' => 80000,
            'default_threshold_max' => 120000,
            'accuracy' => 100,
        ]);
    }

    /**
     * Create a voltage sensor type.
     */
    public function voltage(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Voltage Sensor',
            'data_type' => 'voltage',
            'category' => 'Electrical',
            'unit_of_measure' => 'V',
            'min_value' => 0,
            'max_value' => 480,
            'default_threshold_min' => 100,
            'default_threshold_max' => 250,
            'accuracy' => 0.5,
        ]);
    }

    /**
     * Create a current sensor type.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Current Sensor',
            'data_type' => 'current',
            'category' => 'Electrical',
            'unit_of_measure' => 'A',
            'min_value' => 0,
            'max_value' => 100,
            'default_threshold_min' => 5,
            'default_threshold_max' => 50,
            'accuracy' => 0.1,
        ]);
    }

    /**
     * Create a motion sensor type.
     */
    public function motion(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Motion Sensor',
            'data_type' => 'motion',
            'category' => 'Mechanical',
            'unit_of_measure' => 'm/s',
            'min_value' => 0,
            'max_value' => 10,
            'default_threshold_max' => 2,
            'accuracy' => 0.05,
        ]);
    }

    /**
     * Create a vibration sensor type.
     */
    public function vibration(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Vibration Sensor',
            'data_type' => 'vibration',
            'category' => 'Mechanical',
            'unit_of_measure' => 'Hz',
            'min_value' => 0,
            'max_value' => 1000,
            'default_threshold_max' => 500,
            'accuracy' => 1.0,
        ]);
    }

    /**
     * Create a light sensor type.
     */
    public function light(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Light Sensor',
            'data_type' => 'light',
            'category' => 'Sensing',
            'unit_of_measure' => 'lux',
            'min_value' => 0,
            'max_value' => 100000,
            'default_threshold_min' => 100,
            'default_threshold_max' => 10000,
            'accuracy' => 50,
        ]);
    }

    /**
     * Create a flow sensor type.
     */
    public function flow(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Flow Sensor',
            'data_type' => 'flow',
            'category' => 'Positioning',
            'unit_of_measure' => 'L/min',
            'min_value' => 0,
            'max_value' => 1000,
            'default_threshold_max' => 500,
            'accuracy' => 1.0,
        ]);
    }

    /**
     * Create a level sensor type.
     */
    public function level(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Level Sensor',
            'data_type' => 'level',
            'category' => 'Positioning',
            'unit_of_measure' => 'm',
            'min_value' => 0,
            'max_value' => 10,
            'default_threshold_min' => 1,
            'default_threshold_max' => 8,
            'accuracy' => 0.01,
        ]);
    }

    /**
     * Create an inactive sensor type.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'description' => 'Deprecated sensor type',
        ]);
    }

    /**
     * Create a high-precision sensor type.
     */
    public function highPrecision(): static
    {
        return $this->state(fn (array $attributes) => [
            'accuracy' => $this->faker->randomFloat(4, 0.0001, 0.001),
            'resolution' => $this->faker->randomFloat(6, 0.000001, 0.0001),
            'response_time' => $this->faker->numberBetween(1, 100),
        ]);
    }

    /**
     * Create a low-cost sensor type.
     */
    public function lowCost(): static
    {
        return $this->state(fn (array $attributes) => [
            'accuracy' => $this->faker->randomFloat(4, 0.5, 2.0),
            'resolution' => $this->faker->randomFloat(6, 0.01, 0.1),
            'response_time' => $this->faker->numberBetween(500, 2000),
        ]);
    }
}
