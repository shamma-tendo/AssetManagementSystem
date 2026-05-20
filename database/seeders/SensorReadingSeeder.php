<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SensorReading;
use App\Models\Sensor;
use App\Models\Asset;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SensorReadingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sensors = Sensor::all();
        $assets = Asset::all();
        
        if ($sensors->isEmpty()) {
            $this->command->info('No sensors found. Please run SensorSeeder first.');
            return;
        }

        $sensorReadings = [];
        $batchSize = 500;
        $totalReadings = 10000;

        // Generate readings for the last 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        for ($i = 1; $i <= $totalReadings; $i++) {
            $sensor = $sensors->random();
            $readingTime = $startDate->copy()->addMinutes(rand(0, $endDate->diffInMinutes($startDate)));
            
            // Generate realistic sensor values based on sensor type
            $sensorValue = $this->generateSensorValue($sensor->type, $sensor->unit);
            $quality = $this->determineQuality($sensorValue, $sensor->type);
            $errorCode = $quality === 'poor' ? $this->generateErrorCode() : null;
            
            $sensorReading = [
                'id' => Str::uuid(),
                'sensor_id' => $sensor->id,
                'value' => $sensorValue,
                'unit' => $sensor->unit,
                'quality' => $quality,
                'error_code' => $errorCode,
                'timestamp' => $readingTime,
            ];

            $sensorReadings[] = $sensorReading;

            // Insert in batches
            if (count($sensorReadings) >= $batchSize) {
                SensorReading::insert($sensorReadings);
                $sensorReadings = [];
            }
        }

        // Insert remaining readings
        if (!empty($sensorReadings)) {
            SensorReading::insert($sensorReadings);
        }
    }

    private function generateSensorValue($sensorType, $unit): float
    {
        $ranges = [
            'temperature' => [
                '°C' => [15, 85],
                '°F' => [59, 185],
                'K' => [288, 358]
            ],
            'pressure' => [
                'PSI' => [0, 500],
                'BAR' => [0, 35],
                'KPA' => [0, 3500],
                'MPA' => [0, 3.5]
            ],
            'vibration' => [
                'Hz' => [0, 1000],
                'mm/s' => [0, 50],
                'g' => [0, 10]
            ],
            'flow' => [
                'L/min' => [0, 500],
                'GPM' => [0, 132],
                'm³/h' => [0, 30]
            ],
            'voltage' => [
                'V' => [0, 480],
                'mV' => [0, 5000],
                'kV' => [0, 35]
            ],
            'current' => [
                'A' => [0, 100],
                'mA' => [0, 1000],
                'kA' => [0, 0.1]
            ],
            'speed' => [
                'RPM' => [0, 3600],
                'm/s' => [0, 50],
                'ft/min' => [0, 9842]
            ],
            'level' => [
                '%' => [0, 100],
                'mm' => [0, 1000],
                'in' => [0, 39]
            ],
            'humidity' => [
                '%' => [0, 100],
                'g/m³' => [0, 30]
            ],
            'position' => [
                '°' => [0, 360],
                'mm' => [0, 1000],
                'in' => [0, 39]
            ]
        ];

        if (!isset($ranges[$sensorType][$unit])) {
            return rand(0, 100);
        }

        $range = $ranges[$sensorType][$unit];
        $min = $range[0];
        $max = $range[1];
        
        // Add some realistic variation
        $baseValue = $min + ($max - $min) * 0.6; // Start at 60% of range
        $variation = ($max - $min) * 0.2; // 20% variation
        $value = $baseValue + (rand(-100, 100) / 100) * $variation;
        
        // Ensure value is within range
        return max($min, min($max, $value));
    }

    private function determineQuality($value, $sensorType): string
    {
        // Define quality thresholds based on sensor type and value
        $thresholds = [
            'temperature' => ['excellent' => [20, 40], 'good' => [10, 60], 'fair' => [5, 80], 'poor' => [0, 100]],
            'pressure' => ['excellent' => [50, 150], 'good' => [25, 200], 'fair' => [10, 300], 'poor' => [0, 500]],
            'vibration' => ['excellent' => [0, 5], 'good' => [0, 15], 'fair' => [0, 30], 'poor' => [0, 100]],
            'flow' => ['excellent' => [50, 150], 'good' => [25, 200], 'fair' => [10, 300], 'poor' => [0, 500]],
            'voltage' => ['excellent' => [110, 130], 'good' => [100, 140], 'fair' => [90, 160], 'poor' => [0, 480]],
            'current' => ['excellent' => [10, 50], 'good' => [5, 75], 'fair' => [0, 90], 'poor' => [0, 100]],
            'speed' => ['excellent' => [1000, 2000], 'good' => [500, 3000], 'fair' => [100, 3500], 'poor' => [0, 3600]],
            'level' => ['excellent' => [40, 80], 'good' => [20, 90], 'fair' => [10, 95], 'poor' => [0, 100]],
            'humidity' => ['excellent' => [30, 60], 'good' => [20, 70], 'fair' => [10, 80], 'poor' => [0, 100]],
            'position' => ['excellent' => [90, 270], 'good' => [45, 315], 'fair' => [0, 360], 'poor' => [0, 360]]
        ];

        if (!isset($thresholds[$sensorType])) {
            return 'good'; // Default quality
        }

        $typeThresholds = $thresholds[$sensorType];
        
        foreach (['excellent', 'good', 'fair', 'poor'] as $quality) {
            $range = $typeThresholds[$quality];
            if ($value >= $range[0] && $value <= $range[1]) {
                return $quality;
            }
        }

        return 'fair'; // Default fallback
    }

    private function generateErrorCode(): ?string
    {
        $errorCodes = [
            'SENSOR_HIGH', 'SENSOR_LOW', 'SENSOR_FAULT', 'COMMUNICATION_ERROR',
            'CALIBRATION_ERROR', 'POWER_FAILURE', 'SIGNAL_LOST', 'OUT_OF_RANGE',
            'DRIFT_ERROR', 'NOISE_ERROR', 'INTERFERENCE', 'TIMEOUT_ERROR'
        ];

        // 30% chance of having an error code when quality is poor
        return rand(1, 10) <= 3 ? $errorCodes[array_rand($errorCodes)] : null;
    }
}
