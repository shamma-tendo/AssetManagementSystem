<?php

namespace Database\Factories;

use App\Models\DepreciationMethod;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepreciationMethod>
 */
class DepreciationMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(3),
            'code' => $this->faker->unique()->randomElement(['straight_line', 'declining_balance', 'sum_of_years', 'units_of_production']),
            'formula' => $this->faker->randomElement([
                '(Cost - Salvage) / Useful Life',
                'Book Value × Rate',
                'Remaining Life / Sum of Years × (Cost - Salvage)',
                '(Cost - Salvage) × (Units Used / Total Units)',
            ]),
            'is_active' => true,
            'created_by' => User::factory()->create(['role' => UserRole::MANAGER]),
        ];
    }

    /**
     * Create a straight-line depreciation method.
     */
    public function straightLine(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Straight Line',
            'code' => 'straight_line',
            'description' => 'Equal depreciation over useful life',
            'formula' => '(Cost - Salvage) / Useful Life',
        ]);
    }

    /**
     * Create a declining balance depreciation method.
     */
    public function decliningBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Declining Balance',
            'code' => 'declining_balance',
            'description' => 'Accelerated depreciation method',
            'formula' => 'Book Value × Rate',
        ]);
    }

    /**
     * Create a sum-of-years depreciation method.
     */
    public function sumOfYears(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Sum of Years',
            'code' => 'sum_of_years',
            'description' => 'Sum-of-the-years digits method',
            'formula' => 'Remaining Life / Sum of Years × (Cost - Salvage)',
        ]);
    }

    /**
     * Create a units of production depreciation method.
     */
    public function unitsOfProduction(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Units of Production',
            'code' => 'units_of_production',
            'description' => 'Depreciation based on actual usage',
            'formula' => '(Cost - Salvage) × (Units Used / Total Units)',
        ]);
    }

    /**
     * Create an inactive depreciation method.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'description' => 'Deprecated depreciation method',
        ]);
    }
}
