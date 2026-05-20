<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partCategories = PartCategory::all();
        $suppliers = Supplier::all();
        $users = User::all();
        
        if ($partCategories->isEmpty() || $suppliers->isEmpty()) {
            $this->command->info('No part categories or suppliers found. Please run PartCategorySeeder and SupplierSeeder first.');
            return;
        }

        $parts = [];
        $batchSize = 100;
        $totalParts = 2000;

        // Part types and their characteristics
        $partTypes = [
            'mechanical' => [
                'price_range' => [50, 500],
                'weight_range' => [0.5, 50],
                'lead_time_range' => [3, 21]
            ],
            'electrical' => [
                'price_range' => [25, 300],
                'weight_range' => [0.1, 10],
                'lead_time_range' => [5, 30]
            ],
            'hydraulic' => [
                'price_range' => [100, 800],
                'weight_range' => [1, 100],
                'lead_time_range' => [7, 28]
            ],
            'pneumatic' => [
                'price_range' => [30, 200],
                'weight_range' => [0.2, 20],
                'lead_time_range' => [3, 14]
            ],
            'consumable' => [
                'price_range' => [5, 100],
                'weight_range' => [0.1, 5],
                'lead_time_range' => [1, 7]
            ]
        ];

        for ($i = 1; $i <= $totalParts; $i++) {
            $type = array_rand($partTypes);
            $typeConfig = $partTypes[$type];
            
            $category = $partCategories->random();
            $supplier = $suppliers->random();
            $creator = $users->random();
            
            $unitPrice = rand($typeConfig['price_range'][0], $typeConfig['price_range'][1]);
            $weight = rand($typeConfig['weight_range'][0], $typeConfig['weight_range'][1]);
            $leadTime = rand($typeConfig['lead_time_range'][0], $typeConfig['lead_time_range'][1]);

            $part = [
                'id' => Str::uuid(),
                'name' => $this->generatePartName($type, $i),
                'description' => $this->generatePartDescription($type),
                'part_number' => $this->generatePartNumber($type),
                'category_id' => $category->id,
                'supplier_id' => $supplier->id,
                'unit_cost' => $unitPrice * 0.7,
                'selling_price' => $unitPrice * 1.5,
                'weight_kg' => $weight,
                'unit_of_measure' => $this->getUnitOfMeasure($type),
                'reorder_point' => rand(5, 25),
                'lead_time_days' => $leadTime,
                'supplier_part_number' => $this->generateSupplierPartNumber(),
                'manufacturer_part_number' => $this->generateManufacturerPartNumber(),
                'shelf_life_days' => $type === 'consumable' ? rand(12, 60) * 30 : null,
                'storage_location' => $this->getStorageRequirements($type),
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];

            $parts[] = $part;

            // Insert in batches
            if (count($parts) >= $batchSize) {
                Part::insert($parts);
                $parts = [];
            }
        }

        // Insert remaining parts
        if (!empty($parts)) {
            Part::insert($parts);
        }
    }

    private function generatePartName($type, $index): string
    {
        $names = [
            'mechanical' => [
                'Bearing Assembly', 'Gear Set', 'Shaft Assembly', 'Coupling', 'Seal Kit',
                'Piston Assembly', 'Valve Body', 'Pump Impeller', 'Motor Housing', 'Gearbox'
            ],
            'electrical' => [
                'Circuit Board', 'Relay Module', 'Sensor Assembly', 'Motor Controller', 'Power Supply',
                'Switch Assembly', 'Connector Set', 'Cable Assembly', 'Transformer', 'Capacitor'
            ],
            'hydraulic' => [
                'Hydraulic Cylinder', 'Pump Assembly', 'Valve Assembly', 'Hose Assembly', 'Fitting Set',
                'Seal Kit', 'Filter Element', 'Reservoir Assembly', 'Manifold', 'Actuator'
            ],
            'pneumatic' => [
                'Air Cylinder', 'Valve Assembly', 'Fitting Set', 'Hose Assembly', 'Filter Element',
                'Regulator Assembly', 'Lubricator', 'Actuator', 'Sensor Assembly', 'Control Valve'
            ],
            'consumable' => [
                'Filter Element', 'Lubricant', 'Seal Kit', 'Gasket Set', 'Cleaning Solution',
                'Adhesive', 'Tape Roll', 'Wipe Cloth', 'Glove Set', 'Safety Equipment'
            ]
        ];

        $baseName = $names[$type][array_rand($names[$type])];
        $suffixes = ['Pro', 'Plus', 'Max', 'Ultra', 'Elite', 'Premium', 'Standard', 'Heavy Duty'];
        
        return $baseName . ' ' . $suffixes[$index % count($suffixes)] . ' #' . str_pad($index, 4, '0', STR_PAD_LEFT);
    }

    private function generatePartDescription($type): string
    {
        $descriptions = [
            'mechanical' => 'High-quality mechanical component for industrial equipment maintenance and repair.',
            'electrical' => 'Reliable electrical component for control systems and power distribution.',
            'hydraulic' => 'Durable hydraulic component for fluid power systems and applications.',
            'pneumatic' => 'Efficient pneumatic component for compressed air systems and automation.',
            'consumable' => 'Essential consumable material for daily operations and maintenance activities.'
        ];

        return $descriptions[$type] . ' Meets industry standards and specifications.';
    }

    private function generatePartNumber($type): string
    {
        $prefixes = [
            'mechanical' => 'MEC',
            'electrical' => 'ELC',
            'hydraulic' => 'HYD',
            'pneumatic' => 'PNU',
            'consumable' => 'CON'
        ];

        return $prefixes[$type] . '-' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(3));
    }

    private function generateSupplierPartNumber(): string
    {
        return strtoupper(Str::random(3)) . '-' . rand(1000, 9999) . '-' . strtoupper(Str::random(2));
    }

    private function generateManufacturerPartNumber(): string
    {
        return strtoupper(Str::random(4)) . '-' . rand(10000, 99999) . '-' . strtoupper(Str::random(3));
    }

    private function generateManufacturer(): string
    {
        $manufacturers = [
            'Siemens AG', 'ABB Ltd', 'Rockwell Automation', 'Schneider Electric',
            'Honeywell International', 'Emerson Electric', 'Yokogawa Electric',
            'Mitsubishi Electric', 'Omron Corporation', 'Danfoss A/S',
            'Bosch Rexroth', 'Parker Hannifin', 'Eaton Corporation',
            'General Electric', 'Johnson Controls', 'Carrier Corporation',
            'SKF', 'Timken', 'NSK', 'FAG', 'NTN'
        ];

        return $manufacturers[array_rand($manufacturers)];
    }

    private function getUnitOfMeasure($type): string
    {
        $units = [
            'mechanical' => ['EA', 'SET', 'KIT'],
            'electrical' => ['EA', 'SET', 'ROLL'],
            'hydraulic' => ['EA', 'SET', 'KIT'],
            'pneumatic' => ['EA', 'SET', 'KIT'],
            'consumable' => ['EA', 'BOX', 'CAN', 'L', 'KG']
        ];

        return $units[$type][array_rand($units[$type])];
    }

    private function getStorageRequirements($type): string
    {
        $requirements = [
            'mechanical' => 'Store in dry, temperature-controlled environment. Protect from corrosion.',
            'electrical' => 'Store in climate-controlled environment. Protect from moisture and static.',
            'hydraulic' => 'Store in clean, dry environment. Protect from contamination.',
            'pneumatic' => 'Store in dry environment. Protect from dust and moisture.',
            'consumable' => 'Store according to manufacturer specifications. Check expiry dates.'
        ];

        return $requirements[$type];
    }

    private function getHandlingRequirements($type): string
    {
        $requirements = [
            'mechanical' => 'Handle with care. Use appropriate lifting equipment for heavy items.',
            'electrical' => 'Handle with care. Use anti-static protection where required.',
            'hydraulic' => 'Handle with care. Prevent contamination of hydraulic components.',
            'pneumatic' => 'Handle with care. Protect from impact and damage.',
            'consumable' => 'Handle according to manufacturer specifications. Protect from damage.'
        ];

        return $requirements[$type];
    }
}
