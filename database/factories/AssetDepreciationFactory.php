<?php

namespace Database\Factories;

use App\Models\AssetDepreciation;
use App\Models\Asset;
use App\Models\DepreciationMethod;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<AssetDepreciation>
 */
class AssetDepreciationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $asset = Asset::inRandomOrder()->first() ?? Asset::factory()->create();
        $method = DepreciationMethod::inRandomOrder()->first() ?? DepreciationMethod::factory()->create();
        $creator = User::factory()->create(['role' => UserRole::MANAGER]);

        $purchaseCost = $this->faker->randomFloat(2, 1000, 100000);
        $salvageValue = $purchaseCost * $this->faker->randomFloat(2, 0, 0.2); // 0-20% of purchase cost
        $usefulLifeYears = $this->faker->numberBetween(3, 20);
        $depreciableAmount = $purchaseCost - $salvageValue;
        $annualDepreciation = $depreciableAmount / $usefulLifeYears;
        $monthlyDepreciation = $annualDepreciation / 12;

        return [
            'asset_id' => $asset->id,
            'depreciation_method_id' => $method->id,
            'purchase_cost' => $purchaseCost,
            'salvage_value' => $salvageValue,
            'useful_life_years' => $usefulLifeYears,
            'useful_life_hours' => $this->faker->boolean(30) ? $this->faker->numberBetween(1000, 50000) : null,
            'depreciation_start_date' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'depreciation_end_date' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('now', '+15 years') : null,
            'current_book_value' => $this->faker->randomFloat(2, $salvageValue, $purchaseCost),
            'accumulated_depreciation' => $this->faker->randomFloat(2, 0, $depreciableAmount),
            'annual_depreciation' => $annualDepreciation,
            'monthly_depreciation' => $monthlyDepreciation,
            'depreciation_rate' => $this->calculateDepreciationRate($method->code, $usefulLifeYears),
            'is_active' => true,
            'notes' => $this->faker->boolean(40) ? $this->faker->sentence(3) : null,
            'created_by' => $creator->id,
        ];
    }

    /**
     * Calculate depreciation rate based on method.
     */
    private function calculateDepreciationRate(string $methodCode, int $usefulLifeYears): float
    {
        return match($methodCode) {
            'straight_line' => 1 / $usefulLifeYears,
            'declining_balance' => 2 / $usefulLifeYears, // Double declining balance
            'sum_of_years' => $usefulLifeYears / (($usefulLifeYears * ($usefulLifeYears + 1)) / 2),
            'units_of_production' => 1 / $usefulLifeYears, // Fallback
            default => 1 / $usefulLifeYears,
        };
    }

    /**
     * Create a straight-line depreciation schedule.
     */
    public function straightLine(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_method_id' => DepreciationMethod::where('code', 'straight_line')->first()->id ?? DepreciationMethod::factory()->straightLine()->create()->id,
            'depreciation_rate' => 1 / ($attributes['useful_life_years'] ?? 5),
        ]);
    }

    /**
     * Create a declining balance depreciation schedule.
     */
    public function decliningBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_method_id' => DepreciationMethod::where('code', 'declining_balance')->first()->id ?? DepreciationMethod::factory()->decliningBalance()->create()->id,
            'depreciation_rate' => 2 / ($attributes['useful_life_years'] ?? 5),
        ]);
    }

    /**
     * Create a sum-of-years depreciation schedule.
     */
    public function sumOfYears(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_method_id' => DepreciationMethod::where('code', 'sum_of_years')->first()->id ?? DepreciationMethod::factory()->sumOfYears()->create()->id,
        ]);
    }

    /**
     * Create a partially depreciated asset.
     */
    public function partiallyDepreciated(): static
    {
        return $this->state(fn (array $attributes) => [
            'accumulated_depreciation' => function (array $attributes) {
                $depreciable = $attributes['purchase_cost'] - $attributes['salvage_value'];
                return $depreciable * $this->faker->randomFloat(2, 0.1, 0.8); // 10-80% depreciated
            },
            'current_book_value' => function (array $attributes) {
                return $attributes['purchase_cost'] - $attributes['accumulated_depreciation'];
            },
        ]);
    }

    /**
     * Create a fully depreciated asset.
     */
    public function fullyDepreciated(): static
    {
        return $this->state(fn (array $attributes) => [
            'accumulated_depreciation' => function (array $attributes) {
                return $attributes['purchase_cost'] - $attributes['salvage_value'];
            },
            'current_book_value' => function (array $attributes) {
                return $attributes['salvage_value'];
            },
        ]);
    }

    /**
     * Create a newly acquired asset (no depreciation yet).
     */
    public function newlyAcquired(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_start_date' => now()->addDays($this->faker->numberBetween(1, 30)),
            'accumulated_depreciation' => 0,
            'current_book_value' => function (array $attributes) {
                return $attributes['purchase_cost'];
            },
        ]);
    }

    /**
     * Create an old asset with significant depreciation.
     */
    public function oldAsset(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_start_date' => now()->subYears($this->faker->numberBetween(5, 15)),
            'accumulated_depreciation' => function (array $attributes) {
                $depreciable = $attributes['purchase_cost'] - $attributes['salvage_value'];
                return $depreciable * $this->faker->randomFloat(2, 0.7, 0.95); // 70-95% depreciated
            },
            'current_book_value' => function (array $attributes) {
                return $attributes['purchase_cost'] - $attributes['accumulated_depreciation'];
            },
        ]);
    }

    /**
     * Create a high-value asset.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_cost' => $this->faker->randomFloat(2, 100000, 1000000),
            'salvage_value' => function (array $attributes) {
                return $attributes['purchase_cost'] * $this->faker->randomFloat(2, 0.05, 0.15);
            },
            'useful_life_years' => $this->faker->numberBetween(7, 25),
        ]);
    }

    /**
     * Create a low-value asset.
     */
    public function lowValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_cost' => $this->faker->randomFloat(2, 100, 5000),
            'salvage_value' => function (array $attributes) {
                return $attributes['purchase_cost'] * $this->faker->randomFloat(2, 0, 0.1);
            },
            'useful_life_years' => $this->faker->numberBetween(3, 10),
        ]);
    }

    /**
     * Create an inactive depreciation schedule.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'notes' => 'Depreciation schedule discontinued',
        ]);
    }

    /**
     * Create a depreciation schedule with short useful life.
     */
    public function shortLife(): static
    {
        return $this->state(fn (array $attributes) => [
            'useful_life_years' => $this->faker->numberBetween(1, 3),
            'depreciation_rate' => function (array $attributes) {
                return 1 / ($attributes['useful_life_years'] ?? 3);
            },
        ]);
    }

    /**
     * Create a depreciation schedule with long useful life.
     */
    public function longLife(): static
    {
        return $this->state(fn (array $attributes) => [
            'useful_life_years' => $this->faker->numberBetween(15, 40),
            'depreciation_rate' => function (array $attributes) {
                return 1 / ($attributes['useful_life_years'] ?? 20);
            },
        ]);
    }

    /**
     * Create a depreciation schedule that ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_end_date' => now()->subDays($this->faker->numberBetween(1, 365)),
            'accumulated_depreciation' => function (array $attributes) {
                return $attributes['purchase_cost'] - $attributes['salvage_value'];
            },
            'current_book_value' => function (array $attributes) {
                return $attributes['salvage_value'];
            },
        ]);
    }

    /**
     * Create a depreciation schedule for computer equipment.
     */
    public function computerEquipment(): static
    {
        return $this->state(fn (array $attributes) => [
            'useful_life_years' => $this->faker->numberBetween(3, 5),
            'depreciation_method_id' => DepreciationMethod::where('code', 'straight_line')->first()->id ?? DepreciationMethod::factory()->straightLine()->create()->id,
            'notes' => 'Computer equipment depreciation',
        ]);
    }

    /**
     * Create a depreciation schedule for vehicles.
     */
    public function vehicle(): static
    {
        return $this->state(fn (array $attributes) => [
            'useful_life_years' => $this->faker->numberBetween(5, 8),
            'depreciation_method_id' => DepreciationMethod::where('code', 'declining_balance')->first()->id ?? DepreciationMethod::factory()->decliningBalance()->create()->id,
            'notes' => 'Vehicle depreciation',
        ]);
    }

    /**
     * Create a depreciation schedule for buildings.
     */
    public function building(): static
    {
        return $this->state(fn (array $attributes) => [
            'useful_life_years' => $this->faker->numberBetween(20, 40),
            'depreciation_method_id' => DepreciationMethod::where('code', 'straight_line')->first()->id ?? DepreciationMethod::factory()->straightLine()->create()->id,
            'notes' => 'Building depreciation',
        ]);
    }
}
