<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Asset Categories
            ['id' => Str::uuid(), 'name' => 'machinery', 'description' => 'Industrial machinery and equipment'],
            ['id' => Str::uuid(), 'name' => 'manufacturing', 'description' => 'Manufacturing equipment and tools'],
            ['id' => Str::uuid(), 'name' => 'pumps', 'description' => 'Pumping systems and components'],
            ['id' => Str::uuid(), 'name' => 'fluid_systems', 'description' => 'Fluid handling and control systems'],
            ['id' => Str::uuid(), 'name' => 'vehicles', 'description' => 'Transport and material handling vehicles'],
            ['id' => Str::uuid(), 'name' => 'transport', 'description' => 'Transportation and logistics equipment'],
            ['id' => Str::uuid(), 'name' => 'material_handling', 'description' => 'Material handling and storage equipment'],
            ['id' => Str::uuid(), 'name' => 'automation', 'description' => 'Automation and control systems'],
            ['id' => Str::uuid(), 'name' => 'hvac', 'description' => 'HVAC and climate control systems'],
            ['id' => Str::uuid(), 'name' => 'facilities', 'description' => 'Facility management equipment'],
            ['id' => Str::uuid(), 'name' => 'electrical', 'description' => 'Electrical systems and components'],
            ['id' => Str::uuid(), 'name' => 'control_systems', 'description' => 'Control and monitoring systems'],
            
            // Inventory Categories
            ['id' => Str::uuid(), 'name' => 'spare_parts', 'description' => 'Replacement parts and components'],
            ['id' => Str::uuid(), 'name' => 'mechanical', 'description' => 'Mechanical parts and components'],
            ['id' => Str::uuid(), 'name' => 'consumables', 'description' => 'Consumable materials and supplies'],
            ['id' => Str::uuid(), 'name' => 'lubricants', 'description' => 'Lubricants and fluids'],
            ['id' => Str::uuid(), 'name' => 'tools', 'description' => 'Tools and equipment'],
            ['id' => Str::uuid(), 'name' => 'equipment', 'description' => 'Specialized equipment'],
            ['id' => Str::uuid(), 'name' => 'safety', 'description' => 'Safety equipment and PPE'],
            ['id' => Str::uuid(), 'name' => 'ppe', 'description' => 'Personal protective equipment'],
            ['id' => Str::uuid(), 'name' => 'electrical_components', 'description' => 'Electrical components and parts'],
            ['id' => Str::uuid(), 'name' => 'electronic_components', 'description' => 'Electronic and electrical components'],
        ];

        Category::insert($categories);
    }
}
