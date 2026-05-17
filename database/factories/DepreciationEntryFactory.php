<?php

namespace Database\Factories;

use App\Models\AssetDepreciation;
use App\Models\DepreciationEntry;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends Factory<DepreciationEntry>
 */
class DepreciationEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $depreciation = AssetDepreciation::inRandomOrder()->first() ?? AssetDepreciation::factory()->create();
        $creator = User::factory()->create(['role' => UserRole::MANAGER]);

        $depreciationAmount = $this->faker->randomFloat(2, 50, 5000);
        $bookValueBefore = $depreciation->current_book_value + $depreciationAmount;
        $bookValueAfter = $depreciation->current_book_value;
        $accumulatedBefore = $depreciation->accumulated_depreciation - $depreciationAmount;
        $accumulatedAfter = $depreciation->accumulated_depreciation;

        return [
            'asset_depreciation_id' => $depreciation->id,
            'period_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'depreciation_amount' => $depreciationAmount,
            'book_value_before' => $bookValueBefore,
            'book_value_after' => $bookValueAfter,
            'accumulated_depreciation_before' => $accumulatedBefore,
            'accumulated_depreciation_after' => $accumulatedAfter,
            'description' => $this->faker->boolean(60) ? $this->faker->sentence(3) : "Depreciation for " . $this->faker->monthName() . " " . $this->faker->year(),
            'created_by' => $creator->id,
        ];
    }

    /**
     * Create a monthly depreciation entry.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_date' => Carbon::now()->startOfMonth()->subMonths(rand(0, 12)),
            'description' => "Monthly depreciation for " . Carbon::parse($attributes['period_date'])->format('F Y'),
        ]);
    }

    /**
     * Create an annual depreciation entry.
     */
    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_date' => Carbon::now()->startOfYear()->subYears(rand(0, 3)),
            'depreciation_amount' => function (array $attributes) {
                return $attributes['depreciation_amount'] * 12; // Annual amount
            },
            'description' => "Annual depreciation for " . Carbon::parse($attributes['period_date'])->format('Y'),
        ]);
    }

    /**
     * Create a recent depreciation entry.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_date' => Carbon::now()->subDays(rand(0, 30)),
        ]);
    }

    /**
     * Create an old depreciation entry.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_date' => Carbon::now()->subYears(rand(1, 5))->subMonths(rand(0, 11)),
        ]);
    }

    /**
     * Create a high depreciation amount entry.
     */
    public function highAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_amount' => $this->faker->randomFloat(2, 5000, 50000),
        ]);
    }

    /**
     * Create a low depreciation amount entry.
     */
    public function lowAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_amount' => $this->faker->randomFloat(2, 10, 500),
        ]);
    }

    /**
     * Create an entry for a fully depreciated asset.
     */
    public function fullyDepreciated(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_amount' => function (array $attributes) {
                $depreciation = AssetDepreciation::find($attributes['asset_depreciation_id']);
                return min($attributes['depreciation_amount'], $depreciation->current_book_value - $depreciation->salvage_value);
            },
            'book_value_after' => function (array $attributes) {
                $depreciation = AssetDepreciation::find($attributes['asset_depreciation_id']);
                return max($depreciation->salvage_value, $depreciation->current_book_value - $attributes['depreciation_amount']);
            },
        ]);
    }

    /**
     * Create an entry for the current month.
     */
    public function currentMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_date' => Carbon::now()->startOfMonth(),
            'description' => "Monthly depreciation for " . Carbon::now()->format('F Y'),
        ]);
    }

    /**
     * Create an entry for last month.
     */
    public function lastMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_date' => Carbon::now()->subMonth()->startOfMonth(),
            'description' => "Monthly depreciation for " . Carbon::now()->subMonth()->format('F Y'),
        ]);
    }

    /**
     * Create an entry with specific year.
     */
    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'period_date' => Carbon::create($year, rand(1, 12), 1),
        ]);
    }

    /**
     * Create an entry with specific month and year.
     */
    public function forMonthYear(int $year, int $month): static
    {
        return $this->state(fn (array $attributes) => [
            'period_date' => Carbon::create($year, $month, 1),
        ]);
    }

    /**
     * Create an entry with custom description.
     */
    public function withDescription(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }

    /**
     * Create an entry without description.
     */
    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => null,
        ]);
    }

    /**
     * Create an entry for a specific asset.
     */
    public function forAsset(AssetDepreciation $depreciation): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_depreciation_id' => $depreciation->id,
            'book_value_before' => $depreciation->current_book_value + $attributes['depreciation_amount'],
            'book_value_after' => $depreciation->current_book_value,
            'accumulated_depreciation_before' => $depreciation->accumulated_depreciation - $attributes['depreciation_amount'],
            'accumulated_depreciation_after' => $depreciation->accumulated_depreciation,
        ]);
    }
}
