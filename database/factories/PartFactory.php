<?php

namespace Database\Factories;

use App\Models\Part;
use App\Models\PartCategory;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Part>
 */
class PartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = PartCategory::inRandomOrder()->first() ?? PartCategory::factory()->create();
        $supplier = Supplier::inRandomOrder()->first() ?? Supplier::factory()->create();
        $manufacturer = Supplier::inRandomOrder()->first() ?? Supplier::factory()->create();

        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(4),
            'part_number' => $this->generatePartNumber(),
            'manufacturer_part_number' => $this->faker->bothify('MP-####-##'),
            'supplier_part_number' => $this->faker->bothify('SP-####-##'),
            'category_id' => $category->id,
            'manufacturer_id' => $manufacturer->id,
            'supplier_id' => $supplier->id,
            'unit_of_measure' => $this->faker->randomElement(['PCS', 'KG', 'L', 'M', 'BOX', 'SET', 'PAIR']),
            'current_stock' => $this->faker->randomFloat(2, 0, 1000),
            'minimum_stock' => $this->faker->randomFloat(2, 1, 50),
            'maximum_stock' => $this->faker->randomFloat(2, 100, 2000),
            'reorder_point' => $this->faker->randomFloat(2, 5, 100),
            'reorder_quantity' => $this->faker->randomFloat(2, 10, 500),
            'unit_cost' => $this->faker->randomFloat(2, 0.50, 1000),
            'average_cost' => $this->faker->randomFloat(2, 0.50, 1000),
            'selling_price' => $this->faker->randomFloat(2, 1, 1500),
            'lead_time_days' => $this->faker->numberBetween(1, 90),
            'shelf_life_days' => $this->faker->boolean(30) ? $this->faker->numberBetween(30, 3650) : null,
            'storage_location' => $this->faker->words(2, true),
            'bin_location' => $this->faker->bothify('A##-##'),
            'warehouse_location' => $this->faker->words(2, true),
            'barcode' => $this->faker->ean13(),
            'qr_code' => $this->faker->uuid(),
            'serial_number_required' => $this->faker->boolean(10),
            'batch_number_required' => $this->faker->boolean(15),
            'expiry_date_required' => $this->faker->boolean(20),
            'hazardous_material' => $this->faker->boolean(5),
            'safety_data_sheet_url' => $this->faker->boolean(30) ? $this->faker->url() : null,
            'specifications' => $this->faker->boolean(50) ? $this->generateSpecifications() : null,
            'dimensions' => $this->faker->boolean(60) ? $this->generateDimensions() : null,
            'weight_kg' => $this->faker->randomFloat(3, 0.001, 1000),
            'notes' => $this->faker->boolean(40) ? $this->faker->sentence(3) : null,
            'is_active' => true,
            'created_by' => User::factory()->create(['role' => UserRole::MANAGER]),
        ];
    }

    /**
     * Generate a unique part number.
     */
    private function generatePartNumber(): string
    {
        $prefix = $this->faker->randomElement(['PT', 'CP', 'MP', 'HW', 'SW', 'EL', 'ME']);
        $middle = $this->faker->numerify('####');
        $suffix = $this->faker->randomLetter() . $this->faker->randomLetter();
        
        return "{$prefix}-{$middle}-{$suffix}";
    }

    /**
     * Generate specifications array.
     */
    private function generateSpecifications(): array
    {
        return [
            'material' => $this->faker->randomElement(['Steel', 'Aluminum', 'Plastic', 'Rubber', 'Glass', 'Copper', 'Brass']),
            'color' => $this->faker->colorName,
            'size' => $this->faker->randomElement(['Small', 'Medium', 'Large', 'X-Large']),
            'voltage' => $this->faker->randomElement(['12V', '24V', '110V', '220V', '380V']),
            'power_rating' => $this->faker->randomFloat(2, 1, 1000) . 'W',
            'temperature_range' => $this->faker->randomElement(['-20°C to 80°C', '-40°C to 120°C', '0°C to 60°C']),
            'pressure_rating' => $this->faker->randomFloat(2, 1, 100) . 'bar',
            'certification' => $this->faker->randomElement(['ISO 9001', 'CE', 'UL', 'FCC', 'RoHS']),
        ];
    }

    /**
     * Generate dimensions array.
     */
    private function generateDimensions(): array
    {
        return [
            'length' => $this->faker->randomFloat(2, 1, 1000),
            'width' => $this->faker->randomFloat(2, 1, 500),
            'height' => $this->faker->randomFloat(2, 1, 200),
            'unit' => $this->faker->randomElement(['mm', 'cm', 'm', 'in', 'ft']),
        ];
    }

    /**
     * Indicate that the part has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => $this->faker->randomFloat(2, 0, 10),
            'minimum_stock' => $this->faker->randomFloat(2, 20, 50),
            'reorder_point' => $this->faker->randomFloat(2, 15, 30),
        ]);
    }

    /**
     * Indicate that the part is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => 0,
            'minimum_stock' => $this->faker->randomFloat(2, 10, 50),
            'reorder_point' => $this->faker->randomFloat(2, 5, 25),
        ]);
    }

    /**
     * Indicate that the part needs reordering.
     */
    public function needsReorder(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => $this->faker->randomFloat(2, 0, 20),
            'reorder_point' => $this->faker->randomFloat(2, 25, 50),
            'reorder_quantity' => $this->faker->randomFloat(2, 50, 200),
        ]);
    }

    /**
     * Indicate that the part is overstocked.
     */
    public function overstocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => $this->faker->randomFloat(2, 500, 2000),
            'maximum_stock' => $this->faker->randomFloat(2, 100, 500),
        ]);
    }

    /**
     * Indicate that the part is hazardous material.
     */
    public function hazardous(): static
    {
        return $this->state(fn (array $attributes) => [
            'hazardous_material' => true,
            'safety_data_sheet_url' => $this->faker->url(),
            'specifications' => array_merge($attributes['specifications'] ?? [], [
                'hazard_class' => $this->faker->randomElement(['Flammable', 'Corrosive', 'Toxic', 'Oxidizing']),
                'un_number' => $this->faker->numerify('####'),
            ]),
        ]);
    }

    /**
     * Indicate that the part requires serial numbers.
     */
    public function withSerialNumber(): static
    {
        return $this->state(fn (array $attributes) => [
            'serial_number_required' => true,
            'current_stock' => $this->faker->randomFloat(2, 1, 10), // Lower stock for serialized items
        ]);
    }

    /**
     * Indicate that the part requires batch numbers.
     */
    public function withBatchNumber(): static
    {
        return $this->state(fn (array $attributes) => [
            'batch_number_required' => true,
            'shelf_life_days' => $this->faker->numberBetween(30, 3650),
        ]);
    }

    /**
     * Indicate that the part requires expiry dates.
     */
    public function withExpiryDate(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date_required' => true,
            'shelf_life_days' => $this->faker->numberBetween(30, 3650),
        ]);
    }

    /**
     * Create an electronic component.
     */
    public function electronic(): static
    {
        return $this->state(fn (array $attributes) => [
            'specifications' => array_merge($attributes['specifications'] ?? [], [
                'component_type' => $this->faker->randomElement(['Resistor', 'Capacitor', 'IC', 'Transistor', 'Diode']),
                'voltage_rating' => $this->faker->randomFloat(2, 1, 1000) . 'V',
                'current_rating' => $this->faker->randomFloat(2, 0.1, 100) . 'A',
                'tolerance' => $this->faker->randomElement(['±1%', '±5%', '±10%']),
                'package' => $this->faker->randomElement(['DIP', 'SMD', 'QFN', 'BGA']),
            ]),
            'unit_of_measure' => 'PCS',
            'storage_location' => 'Electronics Storage',
        ]);
    }

    /**
     * Create a mechanical part.
     */
    public function mechanical(): static
    {
        return $this->state(fn (array $attributes) => [
            'specifications' => array_merge($attributes['specifications'] ?? [], [
                'material' => $this->faker->randomElement(['Steel', 'Aluminum', 'Stainless Steel', 'Brass', 'Bronze']),
                'hardness' => $this->faker->randomElement(['HRC 20', 'HRC 40', 'HRC 60', 'HB 150', 'HB 300']),
                'surface_finish' => $this->faker->randomElement(['Polished', 'Machined', 'Ground', 'Coated']),
                'tolerance' => $this->faker->randomElement(['±0.1mm', '±0.05mm', '±0.01mm']),
            ]),
            'unit_of_measure' => 'PCS',
            'weight_kg' => $this->faker->randomFloat(3, 0.1, 100),
        ]);
    }

    /**
     * Create a chemical/consumable part.
     */
    public function consumable(): static
    {
        return $this->state(fn (array $attributes) => [
            'specifications' => array_merge($attributes['specifications'] ?? [], [
                'chemical_composition' => $this->faker->words(3, true),
                'concentration' => $this->faker->randomFloat(2, 1, 100) . '%',
                'ph_level' => $this->faker->randomFloat(2, 0, 14),
                'flash_point' => $this->faker->randomFloat(2, -50, 200) . '°C',
            ]),
            'unit_of_measure' => $this->faker->randomElement(['L', 'ML', 'KG', 'G']),
            'hazardous_material' => $this->faker->boolean(60),
            'batch_number_required' => true,
            'expiry_date_required' => true,
            'shelf_life_days' => $this->faker->numberBetween(30, 1095),
        ]);
    }

    /**
     * Create a fast-moving part.
     */
    public function fastMoving(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => $this->faker->randomFloat(2, 100, 1000),
            'minimum_stock' => $this->faker->randomFloat(2, 50, 200),
            'reorder_point' => $this->faker->randomFloat(2, 75, 300),
            'reorder_quantity' => $this->faker->randomFloat(2, 200, 1000),
            'unit_cost' => $this->faker->randomFloat(2, 1, 50),
        ]);
    }

    /**
     * Create a slow-moving part.
     */
    public function slowMoving(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => $this->faker->randomFloat(2, 5, 50),
            'minimum_stock' => $this->faker->randomFloat(2, 1, 5),
            'reorder_point' => $this->faker->randomFloat(2, 2, 10),
            'reorder_quantity' => $this->faker->randomFloat(2, 5, 25),
            'unit_cost' => $this->faker->randomFloat(2, 50, 1000),
            'shelf_life_days' => $this->faker->boolean(70) ? $this->faker->numberBetween(365, 3650) : null,
        ]);
    }

    /**
     * Create a high-value part.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_cost' => $this->faker->randomFloat(2, 500, 10000),
            'average_cost' => $this->faker->randomFloat(2, 500, 10000),
            'selling_price' => $this->faker->randomFloat(2, 750, 15000),
            'current_stock' => $this->faker->randomFloat(2, 1, 50),
            'minimum_stock' => $this->faker->randomFloat(2, 1, 10),
            'serial_number_required' => true,
            'storage_location' => 'Secure Storage',
        ]);
    }

    /**
     * Create an inactive part.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'current_stock' => 0,
            'notes' => 'Part discontinued or obsolete',
        ]);
    }
}
