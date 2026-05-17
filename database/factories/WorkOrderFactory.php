<?php

namespace Database\Factories;

use App\Models\WorkOrder;
use App\Models\Asset;
use App\Models\User;
use App\Models\UserRole;
use App\Models\WorkOrderStatus;
use App\Models\WorkOrderPriority;
use App\Models\WorkOrderType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $asset = Asset::inRandomOrder()->first() ?? Asset::factory()->create();
        $creator = User::inRandomOrder()->first() ?? User::factory()->create(['role' => UserRole::MANAGER]);
        $technician = User::where('role', UserRole::TECHNICIAN)->inRandomOrder()->first() ?? User::factory()->create(['role' => UserRole::TECHNICIAN]);

        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'status' => $this->faker->randomElement(['requested', 'approved', 'assigned', 'scheduled', 'in_progress', 'on_hold', 'completed']),
            'type' => $this->faker->randomElement(['preventive_maintenance', 'corrective_maintenance', 'inspection', 'repair']),
            'asset_id' => $asset->id,
            'assigned_to' => $this->faker->boolean(70) ? $technician->id : null,
            'created_by' => $creator->id,
            'requested_by' => $this->faker->boolean(80) ? $creator->id : null,
            'location_id' => $asset->location_id,
            'department_id' => $asset->department_id,
            'estimated_hours' => $this->faker->randomFloat(1, 0.5, 40),
            'actual_hours' => $this->faker->randomFloat(1, 0, 50),
            'estimated_cost' => $this->faker->randomFloat(2, 50, 5000),
            'actual_cost' => $this->faker->randomFloat(2, 0, 6000),
            'scheduled_date' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d') : null,
            'started_at' => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
            'completed_at' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'closed_at' => $this->faker->boolean(20) ? $this->faker->dateTimeBetween('-60 days', 'now') : null,
            'notes' => $this->faker->boolean(60) ? $this->faker->paragraph(2) : null,
            'completion_notes' => $this->faker->boolean(40) ? $this->faker->paragraph(2) : null,
            'work_performed' => $this->faker->boolean(50) ? $this->faker->paragraph(3) : null,
            'parts_used' => $this->faker->boolean(30) ? [
                ['name' => $this->faker->word, 'quantity' => $this->faker->numberBetween(1, 5), 'cost' => $this->faker->randomFloat(2, 10, 200)]
            ] : null,
            'tools_used' => $this->faker->boolean(40) ? [
                $this->faker->word, $this->faker->word, $this->faker->word
            ] : null,
            'safety_precautions' => $this->faker->boolean(30) ? $this->faker->paragraph(2) : null,
            'follow_up_required' => $this->faker->boolean(15),
            'follow_up_date' => $this->faker->boolean(10) ? $this->faker->dateTimeBetween('now', '+90 days')->format('Y-m-d') : null,
            'customer_satisfaction' => $this->faker->boolean(60) ? $this->faker->numberBetween(1, 5) : null,
            'internal_notes' => $this->faker->boolean(25) ? $this->faker->paragraph(2) : null,
        ];
    }

    /**
     * Indicate that the work order is requested.
     */
    public function requested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkOrderStatus::REQUESTED,
            'assigned_to' => null,
            'started_at' => null,
            'completed_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Indicate that the work order is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkOrderStatus::IN_PROGRESS,
            'started_at' => now()->subHours(rand(1, 24)),
            'completed_at' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Indicate that the work order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkOrderStatus::COMPLETED,
            'started_at' => now()->subDays(rand(1, 7)),
            'completed_at' => now()->subHours(rand(1, 24)),
            'closed_at' => null,
            'actual_hours' => $this->faker->randomFloat(1, 1, 20),
            'actual_cost' => $this->faker->randomFloat(2, 100, 3000),
            'completion_notes' => $this->faker->paragraph(2),
            'work_performed' => $this->faker->paragraph(3),
        ]);
    }

    /**
     * Indicate that the work order is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkOrderStatus::CLOSED,
            'started_at' => now()->subDays(rand(2, 14)),
            'completed_at' => now()->subDays(rand(1, 7)),
            'closed_at' => now()->subHours(rand(1, 48)),
            'actual_hours' => $this->faker->randomFloat(1, 1, 30),
            'actual_cost' => $this->faker->randomFloat(2, 150, 5000),
            'completion_notes' => $this->faker->paragraph(2),
            'work_performed' => $this->faker->paragraph(3),
            'customer_satisfaction' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * Indicate that the work order is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkOrderStatus::SCHEDULED,
            'scheduled_date' => now()->subDays(rand(1, 7)),
            'priority' => $this->faker->randomElement(['high', 'urgent']),
        ]);
    }

    /**
     * Indicate that the work order is due today.
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkOrderStatus::SCHEDULED,
            'scheduled_date' => today(),
        ]);
    }

    /**
     * Indicate that the work order is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => WorkOrderPriority::HIGH,
        ]);
    }

    /**
     * Indicate that the work order is urgent priority.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => WorkOrderPriority::URGENT,
            'type' => WorkOrderType::EMERGENCY_MAINTENANCE,
        ]);
    }

    /**
     * Indicate that the work order is emergency priority.
     */
    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => WorkOrderPriority::EMERGENCY,
            'type' => WorkOrderType::EMERGENCY_MAINTENANCE,
            'status' => WorkOrderStatus::IN_PROGRESS,
            'started_at' => now()->subMinutes(rand(5, 60)),
        ]);
    }

    /**
     * Indicate that the work order is preventive maintenance.
     */
    public function preventive(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WorkOrderType::PREVENTIVE_MAINTENANCE,
            'priority' => WorkOrderPriority::NORMAL,
        ]);
    }

    /**
     * Indicate that the work order is corrective maintenance.
     */
    public function corrective(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WorkOrderType::CORRECTIVE_MAINTENANCE,
        ]);
    }

    /**
     * Indicate that the work order is an inspection.
     */
    public function inspection(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WorkOrderType::INSPECTION,
            'priority' => WorkOrderPriority::NORMAL,
        ]);
    }
}
