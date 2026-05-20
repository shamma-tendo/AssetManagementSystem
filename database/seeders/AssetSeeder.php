<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all()->keyBy('id');
        $locations = Location::all()->keyBy('id');
        $departments = Department::all()->keyBy('id');
        $users = User::all()->keyBy('id');

        // Asset types and their characteristics
        $assetTypes = [
            'CNC' => [
                'prefix' => 'CNC',
                'categories' => ['machinery', 'manufacturing'],
                'cost_range' => [150000, 500000],
                'status_distribution' => ['active' => 70, 'under_maintenance' => 15, 'ordered' => 10, 'retired' => 5]
            ],
            'PMP' => [
                'prefix' => 'PMP',
                'categories' => ['pumps', 'fluid_systems'],
                'cost_range' => [25000, 150000],
                'status_distribution' => ['active' => 75, 'under_maintenance' => 20, 'ordered' => 5]
            ],
            'TRK' => [
                'prefix' => 'TRK',
                'categories' => ['vehicles', 'transport'],
                'cost_range' => [80000, 200000],
                'status_distribution' => ['active' => 80, 'under_maintenance' => 15, 'ordered' => 3, 'retired' => 2]
            ],
            'CNV' => [
                'prefix' => 'CNV',
                'categories' => ['material_handling', 'automation'],
                'cost_range' => [30000, 120000],
                'status_distribution' => ['active' => 85, 'under_maintenance' => 10, 'ordered' => 5]
            ],
            'HVAC' => [
                'prefix' => 'HVAC',
                'categories' => ['hvac', 'facilities'],
                'cost_range' => [20000, 80000],
                'status_distribution' => ['active' => 90, 'under_maintenance' => 8, 'ordered' => 2]
            ],
            'ELC' => [
                'prefix' => 'ELC',
                'categories' => ['electrical', 'control_systems'],
                'cost_range' => [10000, 60000],
                'status_distribution' => ['active' => 88, 'under_maintenance' => 10, 'ordered' => 2]
            ]
        ];

        $assets = [];
        $batchSize = 100;
        $totalAssets = 1200;

        for ($i = 1; $i <= $totalAssets; $i++) {
            $assetType = $assetTypes[array_rand($assetTypes)];
            $categoryId = $categories->where('name', $assetType['categories'][array_rand($assetType['categories'])])->first()?->id 
                         ?? $categories->random()->id;
            
            $location = $locations->random();
            $department = $departments->random();
            $creator = $users->random();
            
            $status = $this->getWeightedStatus($assetType['status_distribution']);
            $purchaseDate = Carbon::now()->subDays(rand(365, 3650));
            $purchaseCost = rand($assetType['cost_range'][0], $assetType['cost_range'][1]);
            
            $asset = [
                'id' => Str::uuid(),
                'name' => $this->generateAssetName($assetType['prefix'], $i),
                'serial_number' => $assetType['prefix'] . '-' . str_pad($i, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(3)),
                'category_id' => $categoryId,
                'location_id' => $location->id,
                'department_id' => $department->id,
                'purchase_date' => $purchaseDate,
                'purchase_cost' => $purchaseCost,
                'current_value' => $purchaseCost * (rand(60, 95) / 100),
                'salvage_value' => $purchaseCost * 0.1,
                'useful_life_years' => rand(5, 15),
                'depreciation_method' => rand(0, 1) ? 'straight_line' : 'declining_balance',
                'status' => $status,
                'description' => $this->generateAssetDescription($assetType['prefix']),
                'manufacturer' => $this->generateManufacturer(),
                'model' => $assetType['prefix'] . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT),
                'warranty_expiry' => $purchaseDate->copy()->addYears(rand(1, 3)),
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
                'created_at' => $purchaseDate,
                'updated_at' => now(),
            ];

            $assets[] = $asset;

            // Insert in batches to avoid memory issues
            if (count($assets) >= $batchSize) {
                Asset::insert($assets);
                $assets = [];
            }
        }

        // Insert remaining assets
        if (!empty($assets)) {
            Asset::insert($assets);
        }
    }

    private function getWeightedStatus($distribution): string
    {
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($distribution as $status => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'active';
    }

    private function generateAssetName($prefix, $index): string
    {
        $names = [
            'CNC' => ['CNC Milling Machine', 'CNC Lathe', 'CNC Router', 'CNC Plasma Cutter', 'CNC Waterjet'],
            'PMP' => ['Centrifugal Pump', 'Submersible Pump', 'Gear Pump', 'Diaphragm Pump', 'Peristaltic Pump'],
            'TRK' => ['Forklift', 'Pallet Jack', 'Scissor Lift', 'Boom Lift', 'Telehandler'],
            'CNV' => ['Belt Conveyor', 'Roller Conveyor', 'Screw Conveyor', 'Bucket Elevator', 'Chain Conveyor'],
            'HVAC' => ['Air Handler Unit', 'Chiller Unit', 'Roof Top Unit', 'Split System', 'Package Unit'],
            'ELC' => ['Control Panel', 'Motor Control Center', 'PLC System', 'SCADA System', 'Power Distribution']
        ];

        $baseName = $names[$prefix][array_rand($names[$prefix])];
        $suffixes = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta'];
        
        return $baseName . ' ' . $suffixes[$index % count($suffixes)] . ' ' . str_pad($index, 3, '0', STR_PAD_LEFT);
    }

    private function generateAssetDescription($prefix): string
    {
        $descriptions = [
            'CNC' => 'High-precision computer numerical control equipment for manufacturing operations',
            'PMP' => 'Industrial pumping system for fluid transfer and circulation applications',
            'TRK' => 'Material handling equipment for internal transport and logistics operations',
            'CNV' => 'Automated material transport system for production line integration',
            'HVAC' => 'Climate control system for environmental management and comfort',
            'ELC' => 'Electrical control system for automation and power management'
        ];

        return $descriptions[$prefix] ?? 'Industrial equipment asset';
    }

    private function generateManufacturer(): string
    {
        $manufacturers = [
            'Siemens AG', 'ABB Ltd', 'Rockwell Automation', 'Schneider Electric', 
            'Honeywell International', 'Emerson Electric', 'Yokogawa Electric',
            'Mitsubishi Electric', 'Omron Corporation', 'Danfoss A/S',
            'Bosch Rexroth', 'Parker Hannifin', 'Eaton Corporation',
            'General Electric', 'Johnson Controls', 'Carrier Corporation'
        ];

        return $manufacturers[array_rand($manufacturers)];
    }
}
