<?php

namespace Database\Factories;

use App\Models\MaintenanceSchedule;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceSchedule>
 */
class MaintenanceScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $asset = Asset::inRandomOrder()->first() ?? Asset::factory()->create();
        $technician = User::where('role', UserRole::TECHNICIAN)->inRandomOrder()->first() ?? User::factory()->create(['role' => UserRole::TECHNICIAN]);
        $creator = User::inRandomOrder()->first() ?? User::factory()->create(['role' => UserRole::MANAGER]);

        return [
            'asset_id' => $asset->id,
            'title' => $this->faker->sentence(4) . ' Maintenance',
            'description' => $this->faker->paragraph(3),
            'maintenance_type' => $this->faker->randomElement(['preventive', 'predictive', 'corrective', 'routine', 'inspection', 'calibration']),
            'frequency_type' => $this->faker->randomElement(['daily', 'weekly', 'monthly', 'yearly']),
            'frequency_interval' => $this->faker->numberBetween(1, 12),
            'frequency_months' => $this->faker->boolean(70) ? $this->faker->numberBetween(1, 12) : null,
            'frequency_days' => $this->faker->boolean(30) ? $this->faker->numberBetween(1, 30) : null,
            'frequency_hours' => $this->faker->boolean(10) ? $this->faker->numberBetween(1, 24) : null,
            'last_performed_date' => $this->faker->boolean(60) ? $this->faker->dateTimeBetween('-6 months', '-1 week') : null,
            'next_due_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'due_date_based_on' => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('-6 months', 'now') : null,
            'auto_create_work_order' => $this->faker->boolean(70),
            'work_order_priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'assigned_technician_id' => $this->faker->boolean(80) ? $technician->id : null,
            'estimated_duration_hours' => $this->faker->randomFloat(1, 0.5, 40),
            'estimated_cost' => $this->faker->randomFloat(2, 50, 5000),
            'required_parts' => $this->faker->boolean(60) ? [
                ['name' => $this->faker->word, 'quantity' => $this->faker->numberBetween(1, 5)],
                ['name' => $this->faker->word, 'quantity' => $this->faker->numberBetween(1, 3)],
            ] : null,
            'required_tools' => $this->faker->boolean(50) ? [
                $this->faker->word, $this->faker->word, $this->faker->word
            ] : null,
            'safety_requirements' => $this->faker->boolean(40) ? [
                'Wear safety gloves',
                'Use safety glasses',
                'Follow lockout procedures',
            ] : null,
            'checklist_items' => $this->faker->boolean(70) ? [
                'Check oil levels',
                'Inspect belts and hoses',
                'Test safety features',
                'Clean filters',
                'Check for leaks',
            ] : null,
            'is_active' => true,
            'created_by' => $creator->id,
        ];
    }

    /**
     * Indicate that the maintenance schedule is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_due_date' => now()->subDays(rand(1, 30)),
            'auto_create_work_order' => true,
            'work_order_priority' => 'urgent',
        ]);
    }

    /**
     * Indicate that the maintenance schedule is due soon.
     */
    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_due_date' => now()->addDays(rand(1, 15)),
            'auto_create_work_order' => true,
        ]);
    }

    /**
     * Indicate that the maintenance schedule is due today.
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_due_date' => today(),
            'auto_create_work_order' => true,
        ]);
    }

    /**
     * Indicate that the maintenance schedule is preventive maintenance.
     */
    public function preventive(): static
    {
        return $this->state(fn (array $attributes) => [
            'maintenance_type' => 'preventive',
            'frequency_type' => 'monthly',
            'frequency_months' => $this->faker->numberBetween(1, 6),
            'auto_create_work_order' => true,
        ]);
    }

    /**
     * Indicate that the maintenance schedule is an inspection.
     */
    public function inspection(): static
    {
        return $this->state(fn (array $attributes) => [
            'maintenance_type' => 'inspection',
            'frequency_type' => 'yearly',
            'frequency_interval' => 1,
            'estimated_duration_hours' => $this->faker->randomFloat(1, 1, 8),
        ]);
    }

    /**
     * Indicate that the maintenance schedule is routine maintenance.
     */
    public function routine(): static
    {
        return $this->state(fn (array $attributes) => [
            'maintenance_type' => 'routine',
            'frequency_type' => 'weekly',
            'frequency_interval' => 1,
            'estimated_duration_hours' => $this->faker->randomFloat(1, 0.5, 4),
        ]);
    }

    /**
     * Indicate that the maintenance schedule is high frequency (daily/hourly).
     */
    public function highFrequency(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency_type' => $this->faker->randomElement(['daily', 'hourly']),
            'frequency_days' => $this->faker->randomElement([1, 7, 14]),
            'frequency_hours' => $this->faker->randomElement([8, 12, 24]),
            'estimated_duration_hours' => $this->faker->randomFloat(1, 0.5, 2),
        ]);
    }

    /**
     * Indicate that the maintenance schedule auto-creates work orders.
     */
    public function autoCreate(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_create_work_order' => true,
            'work_order_priority' => 'normal',
        ]);
    }

    /**
     * Indicate that the maintenance schedule is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'work_order_priority' => 'high',
            'estimated_cost' => $this->faker->randomFloat(2, 500, 2000),
            'estimated_duration_hours' => $this->faker->randomFloat(1, 2, 12),
        ]);
    }

    /**
     * Indicate that the maintenance schedule is urgent priority.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'work_order_priority' => 'urgent',
            'maintenance_type' => 'corrective',
            'estimated_cost' => $this->faker->randomFloat(2, 1000, 5000),
            'estimated_duration_hours' => $this->faker->randomFloat(1, 4, 24),
        ]);
    }

    /**
     * Indicate that the maintenance schedule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the maintenance schedule has comprehensive checklist.
     */
    public function withChecklist(): static
    {
        return $this->state(fn (array $attributes) => [
            'checklist_items' => [
                'Visual inspection of all components',
                'Check for signs of wear and tear',
                'Test all safety features',
                'Verify proper operation',
                'Check for leaks or unusual noises',
                'Inspect electrical connections',
                'Clean and lubricate moving parts',
                'Document findings and recommendations',
            ],
            'safety_requirements' => [
                'Lockout/tagout procedures',
                'Personal protective equipment required',
                'Ventilation requirements',
                'Fire safety precautions',
            ],
        ]);
    }

    /**
     * Indicate that the maintenance schedule is for a specific asset type.
     */
    public function forAssetType(string $assetType): static
    {
        return $this->state(fn (array $attributes) => match($assetType) {
            'vehicle' => [
                'title' => 'Vehicle Maintenance',
                'maintenance_type' => 'routine',
                'frequency_type' => 'monthly',
                'frequency_months' => 3,
                'required_parts' => [
                    ['name' => 'Engine Oil', 'quantity' => 1],
                    ['name' => 'Oil Filter', 'quantity' => 1],
                    ['name' => 'Air Filter', 'quantity' => 1],
                ],
                'checklist_items' => [
                    'Check oil level and condition',
                    'Inspect tires and pressure',
                    'Check brake fluid',
                    'Test lights and signals',
                    'Inspect belts and hoses',
                ],
            ],
            'server' => [
                'title' => 'Server Maintenance',
                'maintenance_type' => 'preventive',
                'frequency_type' => 'monthly',
                'frequency_months' => 1,
                'required_parts' => [
                    ['name' => 'Backup Tapes', 'quantity' => 4],
                    ['name' => 'Air Filters', 'quantity' => 2],
                ],
                'checklist_items' => [
                    'Check system logs',
                    'Update security patches',
                    'Verify backup systems',
                    'Check hardware temperatures',
                    'Test network connectivity',
                ],
            ],
            'hvac' => [
                'title' => 'HVAC Maintenance',
                'maintenance_type' => 'preventive',
                'frequency_type' => 'monthly',
                'frequency_months' => 3,
                'required_parts' => [
                    ['name' => 'Air Filters', 'quantity' => 2],
                    ['name' => 'Refrigerant', 'quantity' => 1],
                ],
                'checklist_items' => [
                    'Replace air filters',
                    'Check refrigerant levels',
                    'Inspect coils and fins',
                    'Test thermostat operation',
                    'Check for leaks',
                ],
            ],
            default => $attributes,
        });
    }
}
