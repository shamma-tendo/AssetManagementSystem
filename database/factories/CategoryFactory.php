<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'parent_category_id' => null,
            'pm_frequency_months' => $this->faker->numberBetween(1, 12),
            'useful_life_years' => $this->faker->numberBetween(3, 10),
            'depreciation_method' => $this->faker->randomElement(['straight_line', 'declining_balance']),
            'is_active' => true,
        ];
    }
}
