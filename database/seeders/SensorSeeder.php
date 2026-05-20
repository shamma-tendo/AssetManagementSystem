<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sensor;
use App\Models\Asset;
use App\Models\SensorType;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SensorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key constraints temporarily for seeding
        \DB::statement('PRAGMA foreign_keys = OFF');
        
        $assets = Asset::all();
        
        if ($assets->isEmpty()) {
            $this->command->info('No assets found. Please run AssetSeeder first.');
            return;
        }

        $sensors = [];
        $batchSize = 100;

        // Sensor types and their characteristics
        $sensorTypes = [
            'temperature' => [
                'prefix' => 'TEMP',
                'unit' => '°C',
                'description' => 'Temperature monitoring sensor'
            ],
            'pressure' => [
                'prefix' => 'PRES',
                'unit' => 'PSI',
                'description' => 'Pressure monitoring sensor'
            ],
            'vibration' => [
                'prefix' => 'VIB',
                'unit' => 'Hz',
                'description' => 'Vibration monitoring sensor'
            ],
            'flow' => [
                'prefix' => 'FLOW',
                'unit' => 'L/min',
                'description' => 'Flow rate monitoring sensor'
            ],
            'voltage' => [
                'prefix' => 'VOLT',
                'unit' => 'V',
                'description' => 'Voltage monitoring sensor'
            ],
            'current' => [
                'prefix' => 'CURR',
                'unit' => 'A',
                'description' => 'Current monitoring sensor'
            ],
            'speed' => [
                'prefix' => 'SPD',
                'unit' => 'RPM',
                'description' => 'Speed monitoring sensor'
            ],
            'level' => [
                'prefix' => 'LVL',
                'unit' => '%',
                'description' => 'Level monitoring sensor'
            ]
        ];

        foreach ($assets as $asset) {
            // Assign 2-4 sensors per asset based on asset type
            $sensorCount = rand(2, 4);
            $assetSensorTypes = $this->getAssetSensorTypes($asset->name);

            for ($i = 1; $i <= $sensorCount; $i++) {
                $sensorType = $assetSensorTypes[array_rand($assetSensorTypes)];
                $typeConfig = $sensorTypes[$sensorType];

                $sensor = [
                    'id' => Str::uuid(),
                    'asset_id' => $asset->id,
                    'sensor_type_id' => $this->getSensorTypeId($sensorType),
                    'name' => $typeConfig['prefix'] . '-' . str_pad($asset->id, 8, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'description' => $typeConfig['description'] . ' for ' . $asset->name,
                    'model' => $typeConfig['prefix'] . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                    'manufacturer' => $this->generateSensorManufacturer(),
                    'serial_number' => $typeConfig['prefix'] . '-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                    'threshold_min' => $this->getThresholdMin($sensorType),
                    'threshold_max' => $this->getThresholdMax($sensorType),
                    'calibration_date' => Carbon::now()->subDays(rand(1, 90)),
                    'next_calibration_date' => Carbon::now()->addDays(rand(30, 180)),
                    'installation_date' => $asset->purchase_date->copy()->addDays(rand(1, 30)),
                    'status' => 'active',
                    'location_description' => $this->getSensorLocation($asset->name),
                    'created_by' => $asset->created_by,
                    'updated_by' => $asset->updated_by,
                    'created_at' => $asset->purchase_date->copy()->addDays(rand(1, 30)),
                    'updated_at' => now(),
                ];

                $sensors[] = $sensor;

                // Insert in batches
                if (count($sensors) >= $batchSize) {
                    Sensor::insert($sensors);
                    $sensors = [];
                }
            }
        }

        // Insert remaining sensors
        if (!empty($sensors)) {
            Sensor::insert($sensors);
        }
    }

    private function getAssetSensorTypes($assetName): array
    {
        // Determine appropriate sensor types based on asset name
        if (stripos($assetName, 'CNC') !== false || stripos($assetName, 'Mill') !== false) {
            return ['temperature', 'vibration', 'speed', 'current'];
        } elseif (stripos($assetName, 'PMP') !== false || stripos($assetName, 'Pump') !== false) {
            return ['pressure', 'flow', 'temperature', 'vibration'];
        } elseif (stripos($assetName, 'HVAC') !== false || stripos($assetName, 'Air') !== false) {
            return ['temperature', 'pressure', 'flow', 'level'];
        } elseif (stripos($assetName, 'ELC') !== false || stripos($assetName, 'Control') !== false) {
            return ['voltage', 'current', 'temperature'];
        } elseif (stripos($assetName, 'CNV') !== false || stripos($assetName, 'Conveyor') !== false) {
            return ['speed', 'vibration', 'temperature', 'level'];
        } else {
            return ['temperature', 'vibration', 'pressure', 'level'];
        }
    }

    private function generateSensorManufacturer(): string
    {
        $manufacturers = [
            'Siemens AG', 'ABB Ltd', 'Honeywell International', 'Emerson Electric',
            'Yokogawa Electric', 'Schneider Electric', 'Rockwell Automation',
            'Endress+Hauser', 'Rosemount', 'Foxboro', 'Fisher-Rosemount',
            'Baumer', 'Pepperl+Fuchs', 'Turck', 'Sick AG'
        ];

        return $manufacturers[array_rand($manufacturers)];
    }

    private function getMinValue($sensorType): float
    {
        $ranges = [
            'temperature' => -50,
            'pressure' => 0,
            'vibration' => 0,
            'flow' => 0,
            'voltage' => 0,
            'current' => 0,
            'speed' => 0,
            'level' => 0
        ];

        return $ranges[$sensorType] ?? 0;
    }

    private function getMaxValue($sensorType): float
    {
        $ranges = [
            'temperature' => 200,
            'pressure' => 1000,
            'vibration' => 2000,
            'flow' => 1000,
            'voltage' => 600,
            'current' => 200,
            'speed' => 5000,
            'level' => 100
        ];

        return $ranges[$sensorType] ?? 100;
    }

    private function getThresholdMin($sensorType): float
    {
        $ranges = [
            'temperature' => 10,
            'pressure' => 10,
            'vibration' => 50,
            'flow' => 10,
            'voltage' => 90,
            'current' => 1,
            'speed' => 100,
            'level' => 20
        ];

        return $ranges[$sensorType] ?? 0;
    }

    private function getThresholdMax($sensorType): float
    {
        $ranges = [
            'temperature' => 80,
            'pressure' => 800,
            'vibration' => 1000,
            'flow' => 800,
            'voltage' => 480,
            'current' => 150,
            'speed' => 3000,
            'level' => 80
        ];

        return $ranges[$sensorType] ?? 100;
    }

    private function getSensorLocation($assetName): string
    {
        $locations = [
            'Main housing', 'Motor housing', 'Pump housing', 'Control panel',
            'Primary chamber', 'Secondary chamber', 'Input port', 'Output port',
            'Upper section', 'Lower section', 'Front panel', 'Rear panel'
        ];

        return $locations[array_rand($locations)];
    }
    
    /**
     * Get sensor type ID by type name
     */
    private function getSensorTypeId($sensorType): string
    {
        // Map sensor types to existing sensor type IDs
        // This is a simplified approach - in production, you'd query the database
        $sensorTypeMap = [
            'temperature' => '00000000-0000-0000-0000-000000000001',
            'pressure' => '00000000-0000-0000-0000-000000000002',
            'vibration' => '00000000-0000-0000-0000-000000000003',
            'flow' => '00000000-0000-0000-0000-000000000004',
            'voltage' => '00000000-0000-0000-0000-000000000005',
            'current' => '00000000-0000-0000-0000-000000000006',
            'speed' => '00000000-0000-0000-0000-000000000007',
            'level' => '00000000-0000-0000-0000-000000000008',
        ];
        
        return $sensorTypeMap[$sensorType] ?? '00000000-0000-0000-0000-000000000001';
    }
    
    /**
     * Re-enable foreign key constraints after seeding
     */
    public function __destruct()
    {
        // Re-enable foreign key constraints
        \DB::statement('PRAGMA foreign_keys = ON');
    }
}
