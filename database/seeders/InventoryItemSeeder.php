<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();
        $locations = Location::all();
        $users = User::all();
        
        if ($categories->isEmpty() || $users->isEmpty()) {
            $this->command->info('No categories or users found. Please run CategorySeeder and UserSeeder first.');
            return;
        }

        $inventoryItems = [];
        $batchSize = 100;
        $totalItems = 1500;

        // Inventory item types and their characteristics
        $itemTypes = [
            'spare_parts' => [
                'categories' => ['spare_parts', 'mechanical'],
                'price_range' => [10, 500],
                'stock_range' => [5, 100],
                'reorder_point' => [10, 25]
            ],
            'consumables' => [
                'categories' => ['consumables', 'lubricants'],
                'price_range' => [5, 100],
                'stock_range' => [20, 500],
                'reorder_point' => [25, 50]
            ],
            'tools' => [
                'categories' => ['tools', 'equipment'],
                'price_range' => [25, 300],
                'stock_range' => [2, 20],
                'reorder_point' => [3, 8]
            ],
            'safety' => [
                'categories' => ['safety', 'ppe'],
                'price_range' => [15, 200],
                'stock_range' => [10, 100],
                'reorder_point' => [15, 30]
            ],
            'electrical' => [
                'categories' => ['electrical', 'components'],
                'price_range' => [20, 400],
                'stock_range' => [3, 30],
                'reorder_point' => [5, 12]
            ]
        ];

        for ($i = 1; $i <= $totalItems; $i++) {
            $itemType = array_rand($itemTypes);
            $typeConfig = $itemTypes[$itemType];
            
            $category = $categories->whereIn('name', $typeConfig['categories'])->random();
            $location = $locations->random();
            $creator = $users->random();
            
            $unitPrice = rand($typeConfig['price_range'][0], $typeConfig['price_range'][1]);
            $currentStock = rand($typeConfig['stock_range'][0], $typeConfig['stock_range'][1]);
            $reorderLevel = rand($typeConfig['reorder_point'][0], $typeConfig['reorder_point'][1]);
            $reorderQuantity = $reorderLevel * 2;
            
            // Calculate stock status
            $stockStatus = $this->calculateStockStatus($currentStock, $reorderLevel);
            
            $inventoryItem = [
                'id' => Str::uuid(),
                'name' => $this->generateItemName($itemType, $i),
                'sku' => $this->generateSKU($itemType, $i),
                'description' => $this->generateItemDescription($itemType),
                'category_id' => $category->id,
                'location_id' => $location->id,
                'unit_price' => $unitPrice,
                'current_stock' => $currentStock,
                'reorder_level' => $reorderLevel,
                'reorder_quantity' => $reorderQuantity,
                'unit_of_measure' => $this->getUnitOfMeasure($itemType),
                'supplier' => $this->generateSupplier(),
                'supplier_part_number' => $this->generateSupplierPartNumber(),
                'manufacturer' => $this->generateManufacturer(),
                'manufacturer_part_number' => $this->generateManufacturerPartNumber(),
                'lead_time_days' => rand(3, 21),
                'minimum_stock' => $reorderLevel * 0.5,
                'maximum_stock' => $reorderLevel * 3,
                'stock_status' => $stockStatus,
                'last_count_date' => Carbon::now()->subDays(rand(1, 30)),
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];

            $inventoryItems[] = $inventoryItem;

            // Insert in batches
            if (count($inventoryItems) >= $batchSize) {
                InventoryItem::insert($inventoryItems);
                $inventoryItems = [];
            }
        }

        // Insert remaining items
        if (!empty($inventoryItems)) {
            InventoryItem::insert($inventoryItems);
        }
    }

    private function calculateStockStatus($currentStock, $reorderLevel): string
    {
        if ($currentStock == 0) {
            return 'out_of_stock';
        } elseif ($currentStock <= $reorderLevel * 0.25) {
            return 'critical';
        } elseif ($currentStock <= $reorderLevel) {
            return 'low';
        } elseif ($currentStock >= $reorderLevel * 2) {
            return 'overstock';
        } else {
            return 'normal';
        }
    }

    private function generateItemName($type, $index): string
    {
        $names = [
            'spare_parts' => [
                'Bearing Assembly', 'Seal Kit', 'Gasket Set', 'Filter Element', 'Pump Impeller',
                'Valve Assembly', 'Motor Bearing', 'Gear Set', 'Coupling', 'Shaft Assembly'
            ],
            'consumables' => [
                'Lubricating Oil', 'Hydraulic Fluid', 'Cleaning Solution', 'Grease Cartridge',
                'Filter Media', 'Adhesive Compound', 'Sealant', 'Coolant', 'Solvent', 'Degreaser'
            ],
            'tools' => [
                'Torque Wrench', 'Socket Set', 'Pliers Set', 'Screwdriver Kit', 'Allen Key Set',
                'Multimeter', 'Caliper', 'Pressure Gauge', 'Flow Meter', 'Thermometer'
            ],
            'safety' => [
                'Safety Glasses', 'Hard Hat', 'Safety Gloves', 'Ear Plugs', 'Respirator',
                'Safety Vest', 'Face Shield', 'Safety Boots', 'First Aid Kit', 'Fire Extinguisher'
            ],
            'electrical' => [
                'Circuit Breaker', 'Relay Module', 'Contactor', 'Fuse Set', 'Terminal Block',
                'Wire Connector', 'Switch Assembly', 'Sensor Module', 'Control Relay', 'Power Supply'
            ]
        ];

        $baseName = $names[$type][array_rand($names[$type])];
        $suffixes = ['Pro', 'Plus', 'Max', 'Ultra', 'Elite', 'Premium', 'Standard', 'Heavy Duty'];
        
        return $baseName . ' ' . $suffixes[$index % count($suffixes)] . ' #' . str_pad($index, 4, '0', STR_PAD_LEFT);
    }

    private function generateSKU($type, $index): string
    {
        $prefixes = [
            'spare_parts' => 'SP',
            'consumables' => 'CN',
            'tools' => 'TL',
            'safety' => 'SF',
            'electrical' => 'EL'
        ];

        return $prefixes[$type] . '-' . str_pad($index, 5, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(2));
    }

    private function generateItemDescription($type): string
    {
        $descriptions = [
            'spare_parts' => 'High-quality replacement part for industrial equipment maintenance and repair',
            'consumables' => 'Essential consumable material for daily operations and maintenance activities',
            'tools' => 'Professional-grade tool for maintenance and repair operations',
            'safety' => 'Personal protective equipment for workplace safety compliance',
            'electrical' => 'Electrical component for control systems and power distribution'
        ];

        return $descriptions[$type] . '. Meets industry standards and specifications.';
    }

    private function getUnitOfMeasure($type): string
    {
        $units = [
            'spare_parts' => ['EA', 'SET', 'KIT'],
            'consumables' => ['L', 'KG', 'GAL', 'CAN'],
            'tools' => ['EA', 'SET'],
            'safety' => ['EA', 'BOX', 'PAIR'],
            'electrical' => ['EA', 'SET', 'ROLL']
        ];

        return $units[$type][array_rand($units[$type])];
    }

    private function generateSupplier(): string
    {
        $suppliers = [
            'Grainger Industrial Supply', 'Fastenal Company', 'MSC Industrial Supply',
            'Zoro Tools', 'Global Industrial', 'RS Components', 'Digi-Key Electronics',
            'Newark Electronics', 'Mouser Electronics', 'AutomationDirect',
            'Honeywell Safety Products', '3M Industrial', 'Miller Electric',
            'Fluke Corporation', 'Baldor Electric', 'Eaton Corporation'
        ];

        return $suppliers[array_rand($suppliers)];
    }

    private function generateSupplierPartNumber(): string
    {
        return strtoupper(Str::random(3)) . '-' . rand(1000, 9999) . '-' . strtoupper(Str::random(2));
    }

    private function generateManufacturer(): string
    {
        $manufacturers = [
            'Siemens AG', 'ABB Ltd', 'Rockwell Automation', 'Schneider Electric',
            'Honeywell International', 'Emerson Electric', '3M Corporation',
            'General Electric', 'Baldor Electric', 'Eaton Corporation',
            'Fluke Corporation', 'Miller Electric', 'Grainger Industrial',
            'Fastenal Corporation', 'MSC Industrial Supply', 'Zoro Tools'
        ];

        return $manufacturers[array_rand($manufacturers)];
    }

    private function generateManufacturerPartNumber(): string
    {
        return strtoupper(Str::random(4)) . '-' . rand(10000, 99999) . '-' . strtoupper(Str::random(3));
    }
}
