<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchaseCost = $this->faker->numberBetween(1000, 50000);
        $purchaseDate = $this->faker->dateTimeBetween('-5 years', 'now');

        return [
            'name' => $this->faker->words(3, true),
            'serial_number' => $this->faker->unique()->bothify('SN-####-????'),
            'category_id' => Category::factory(),
            'location_id' => Location::factory(),
            'department_id' => Department::factory(),
            'purchase_date' => $purchaseDate,
            'purchase_cost' => $purchaseCost,
            'current_value' => $purchaseCost,
            'salvage_value' => $purchaseCost * 0.1,
            'useful_life_years' => $this->faker->numberBetween(3, 10),
            'depreciation_method' => $this->faker->randomElement(['straight_line', 'declining_balance']),
            'status' => $this->faker->randomElement(['ordered', 'received', 'active', 'under_maintenance', 'retired']),
            'description' => $this->faker->sentence(),
            'manufacturer' => $this->faker->company(),
            'model' => $this->faker->bothify('Model-####'),
            'warranty_expiry' => $this->faker->dateTimeBetween('now', '+5 years'),
        ];
    }

    /**
     * Indicate that the asset is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the asset is under maintenance.
     */
    public function underMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_maintenance',
        ]);
    }

    /**
     * Indicate that the asset is retired.
     */
    public function retired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'retired',
        ]);
    }
}
