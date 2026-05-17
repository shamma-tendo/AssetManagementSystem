<?php

namespace App\Services;

use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\SensorAlert;
use App\Models\SensorCalibration;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IoTService
{
    /**
     * Generate sensor analytics for a specific period.
     */
    public function generateSensorAnalytics(Sensor $sensor, string $period, array $options = []): array
    {
        $startDate = $options['start_date'] ?? $this->getStartDateForPeriod($period);
        $endDate = $options['end_date'] ?? now();

        $readings = $sensor->readings()
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp')
            ->get();

        if ($readings->isEmpty()) {
            return [
                'sensor_id' => $sensor->id,
                'sensor_name' => $sensor->name,
                'period' => $period,
                'start_date' => $startDate->toISOString(),
                'end_date' => $endDate->toISOString(),
                'total_readings' => 0,
                'statistics' => [],
                'trends' => [],
                'anomalies' => [],
            ];
        }

        $analytics = [
            'sensor_id' => $sensor->id,
            'sensor_name' => $sensor->name,
            'sensor_type' => $sensor->sensorType->name,
            'period' => $period,
            'start_date' => $startDate->toISOString(),
            'end_date' => $endDate->toISOString(),
            'total_readings' => $readings->count(),
            'statistics' => $this->calculateStatistics($readings),
            'trends' => $this->analyzeTrends($readings, $period),
            'anomalies' => $this->detectAnomalies($readings),
            'quality_metrics' => $this->calculateQualityMetrics($readings),
        ];

        return $analytics;
    }

    /**
     * Generate comprehensive health report.
     */
    public function generateHealthReport(): array
    {
        $sensors = Sensor::with(['sensorType', 'asset', 'latestReading'])->get();

        $report = [
            'summary' => [
                'total_sensors' => $sensors->count(),
                'healthy_sensors' => 0,
                'sensors_with_issues' => 0,
                'critical_issues' => 0,
                'overall_health_score' => 0,
            ],
            'by_status' => [],
            'by_type' => [],
            'by_asset' => [],
            'issues' => [],
            'recommendations' => [],
            'generated_at' => now()->toISOString(),
        ];

        foreach ($sensors as $sensor) {
            $healthStatus = $sensor->health_status;
            $report['by_status'][$healthStatus] = ($report['by_status'][$healthStatus] ?? 0) + 1;

            if ($healthStatus === 'healthy') {
                $report['summary']['healthy_sensors']++;
            } else {
                $report['summary']['sensors_with_issues']++;
                $report['issues'][] = [
                    'sensor_id' => $sensor->id,
                    'sensor_name' => $sensor->name,
                    'asset_name' => $sensor->asset->name,
                    'issue_type' => $healthStatus,
                    'severity' => $this->getIssueSeverity($healthStatus),
                    'recommendations' => $sensor->maintenance_recommendations,
                ];
            }

            if ($this->getIssueSeverity($healthStatus) === 'critical') {
                $report['summary']['critical_issues']++;
            }

            // Group by sensor type
            $sensorType = $sensor->sensorType->name;
            $report['by_type'][$sensorType] = ($report['by_type'][$sensorType] ?? 0) + 1;

            // Group by asset
            $assetName = $sensor->asset->name;
            $report['by_asset'][$assetName] = ($report['by_asset'][$assetName] ?? 0) + 1;
        }

        // Calculate overall health score
        if ($report['summary']['total_sensors'] > 0) {
            $report['summary']['overall_health_score'] = 
                ($report['summary']['healthy_sensors'] / $report['summary']['total_sensors']) * 100;
        }

        // Generate recommendations
        $report['recommendations'] = $this->generateHealthRecommendations($report['issues']);

        return $report;
    }

    /**
     * Process batch readings from IoT device.
     */
    public function processBatchReadings(string $sensorId, array $readings, array $metadata = []): array
    {
        $sensor = Sensor::findOrFail($sensorId);
        $results = [
            'processed' => 0,
            'failed' => 0,
            'alerts_created' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($readings as $readingData) {
                try {
                    // Add metadata to each reading
                    $readingData['metadata'] = array_merge($metadata, $readingData['metadata'] ?? []);
                    
                    $reading = SensorReading::createValidated([
                        'sensor_id' => $sensorId,
                        'value' => $readingData['value'],
                        'timestamp' => $readingData['timestamp'],
                        'quality' => $readingData['quality'] ?? 1.0,
                        'battery_level' => $readingData['battery_level'] ?? null,
                        'signal_strength' => $readingData['signal_strength'] ?? null,
                        'metadata' => $readingData['metadata'] ?? [],
                    ]);

                    $results['processed']++;

                    // Check for alerts
                    if ($sensor->alert_enabled) {
                        $alerts = $this->checkForAlerts($sensor, $reading);
                        $results['alerts_created'] += count($alerts);
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'timestamp' => $readingData['timestamp'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Update sensor heartbeat
            $sensor->update([
                'last_heartbeat' => now(),
                'battery_level' => end($readings)['battery_level'] ?? $sensor->battery_level,
                'signal_strength' => end($readings)['signal_strength'] ?? $sensor->signal_strength,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Get start date for period.
     */
    private function getStartDateForPeriod(string $period): Carbon
    {
        return match($period) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
        };
    }

    /**
     * Calculate statistics for readings.
     */
    private function calculateStatistics($readings): array
    {
        $values = $readings->pluck('value')->filter();
        
        if ($values->isEmpty()) {
            return [];
        }

        return [
            'min' => $values->min(),
            'max' => $values->max(),
            'average' => $values->avg(),
            'median' => $this->calculateMedian($values->toArray()),
            'standard_deviation' => $this->calculateStandardDeviation($values->toArray()),
            'variance' => $this->calculateVariance($values->toArray()),
            'range' => $values->max() - $values->min(),
            'count' => $values->count(),
        ];
    }

    /**
     * Analyze trends in readings.
     */
    private function analyzeTrends($readings, string $period): array
    {
        $values = $readings->pluck('value');
        
        if ($values->count() < 2) {
            return [
                'trend' => 'insufficient_data',
                'slope' => 0,
                'correlation' => 0,
                'seasonal_pattern' => false,
            ];
        }

        $x = range(0, $values->count() - 1);
        $slope = $this->calculateLinearRegression($x, $values->toArray());
        
        $trend = 'stable';
        if (abs($slope) > 0.1) {
            $trend = $slope > 0 ? 'increasing' : 'decreasing';
        }

        return [
            'trend' => $trend,
            'slope' => $slope,
            'correlation' => $this->calculateCorrelation($x, $values->toArray()),
            'seasonal_pattern' => $this->detectSeasonalPattern($readings),
            'change_rate' => $this->calculateChangeRate($values),
        ];
    }

    /**
     * Detect anomalies in readings.
     */
    private function detectAnomalies($readings): array
    {
        $anomalies = [];
        $values = $readings->pluck('value');
        
        if ($values->count() < 10) {
            return $anomalies;
        }

        $mean = $values->avg();
        $stdDev = $this->calculateStandardDeviation($values->toArray());
        
        if ($stdDev == 0) {
            return $anomalies;
        }

        foreach ($readings as $reading) {
            $zScore = abs($reading->value - $mean) / $stdDev;
            
            if ($zScore > 3) { // Threshold for anomaly
                $anomalies[] = [
                    'timestamp' => $reading->timestamp->toISOString(),
                    'value' => $reading->value,
                    'z_score' => $zScore,
                    'severity' => $zScore > 4 ? 'high' : 'medium',
                    'type' => 'statistical_outlier',
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Calculate quality metrics.
     */
    private function calculateQualityMetrics($readings): array
    {
        $qualities = $readings->pluck('quality');
        
        if ($qualities->isEmpty()) {
            return [];
        }

        return [
            'average_quality' => $qualities->avg(),
            'min_quality' => $qualities->min(),
            'max_quality' => $qualities->max(),
            'good_quality_percentage' => $qualities->filter(fn($q) => $q >= 0.8)->count() / $qualities->count() * 100,
            'poor_quality_percentage' => $qualities->filter(fn($q) => $q < 0.5)->count() / $qualities->count() * 100,
            'quality_variance' => $this->calculateVariance($qualities->toArray()),
        ];
    }

    /**
     * Calculate median.
     */
    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        } else {
            return $values[($count - 1) / 2];
        }
    }

    /**
     * Calculate standard deviation.
     */
    private function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0;
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values)) / ($count - 1);

        return sqrt($variance);
    }

    /**
     * Calculate variance.
     */
    private function calculateVariance(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0;
        }

        $mean = array_sum($values) / $count;
        return array_sum(array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values)) / ($count - 1);
    }

    /**
     * Calculate linear regression slope.
     */
    private function calculateLinearRegression(array $x, array $y): float
    {
        $n = count($x);
        if ($n < 2) {
            return 0;
        }

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(fn($xi, $yi) => $xi * $yi, $x, $y));
        $sumX2 = array_sum(array_map(fn($xi) => $xi * $xi, $x));

        $denominator = $n * $sumX2 - $sumX * $sumX;
        if ($denominator == 0) {
            return 0;
        }

        return ($n * $sumXY - $sumX * $sumY) / $denominator;
    }

    /**
     * Calculate correlation coefficient.
     */
    private function calculateCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n < 2) {
            return 0;
        }

        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $numerator = 0;
        $denominatorX = 0;
        $denominatorY = 0;

        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $meanX;
            $dy = $y[$i] - $meanY;
            $numerator += $dx * $dy;
            $denominatorX += $dx * $dx;
            $denominatorY += $dy * $dy;
        }

        $denominator = sqrt($denominatorX * $denominatorY);
        if ($denominator == 0) {
            return 0;
        }

        return $numerator / $denominator;
    }

    /**
     * Detect seasonal patterns.
     */
    private function detectSeasonalPattern($readings): bool
    {
        // Simplified seasonal pattern detection
        // In a real implementation, this would use more sophisticated algorithms
        $values = $readings->pluck('value');
        
        if ($values->count() < 24) { // Need at least 24 data points
            return false;
        }

        // Check for periodic patterns by comparing values at regular intervals
        $periodicity = $this->findPeriodicity($values->toArray());
        
        return $periodicity > 0;
    }

    /**
     * Find periodicity in data.
     */
    private function findPeriodicity(array $values): int
    {
        // Simplified periodicity detection
        // Returns the period length if found, 0 otherwise
        $n = count($values);
        
        for ($period = 2; $period <= $n / 2; $period++) {
            $correlation = 0;
            $count = 0;
            
            for ($i = 0; $i < $n - $period; $i++) {
                $correlation += abs($values[$i] - $values[$i + $period]);
                $count++;
            }
            
            if ($count > 0) {
                $avgDiff = $correlation / $count;
                if ($avgDiff < 0.1 * ($values[$n - 1] - $values[0])) { // Threshold for periodicity
                    return $period;
                }
            }
        }
        
        return 0;
    }

    /**
     * Calculate change rate.
     */
    private function calculateChangeRate($values): float
    {
        if ($values->count() < 2) {
            return 0;
        }

        $first = $values->first();
        $last = $values->last();
        
        if ($first == 0) {
            return 0;
        }

        return (($last - $first) / $first) * 100;
    }

    /**
     * Get issue severity.
     */
    private function getIssueSeverity(string $healthStatus): string
    {
        return match($healthStatus) {
            'offline', 'low_battery' => 'critical',
            'poor_signal', 'needs_calibration' => 'medium',
            'inactive' => 'low',
            default => 'low',
        };
    }

    /**
     * Generate health recommendations.
     */
    private function generateHealthRecommendations(array $issues): array
    {
        $recommendations = [];
        $issueCounts = [];

        // Count issues by type
        foreach ($issues as $issue) {
            $issueCounts[$issue['issue_type']] = ($issueCounts[$issue['issue_type']] ?? 0) + 1;
        }

        // Generate recommendations based on issue patterns
        if (isset($issueCounts['offline']) && $issueCounts['offline'] > 0) {
            $recommendations[] = [
                'type' => 'connectivity',
                'priority' => 'high',
                'message' => "{$issueCounts['offline']} sensors are offline",
                'action' => 'Check power and network connectivity for offline sensors',
                'affected_sensors' => $issueCounts['offline'],
            ];
        }

        if (isset($issueCounts['low_battery']) && $issueCounts['low_battery'] > 0) {
            $recommendations[] = [
                'type' => 'maintenance',
                'priority' => 'medium',
                'message' => "{$issueCounts['low_battery']} sensors have low battery",
                'action' => 'Replace or recharge batteries in affected sensors',
                'affected_sensors' => $issueCounts['low_battery'],
            ];
        }

        if (isset($issueCounts['needs_calibration']) && $issueCounts['needs_calibration'] > 0) {
            $recommendations[] = [
                'type' => 'calibration',
                'priority' => 'medium',
                'message' => "{$issueCounts['needs_calibration']} sensors need calibration",
                'action' => 'Schedule calibration maintenance for affected sensors',
                'affected_sensors' => $issueCounts['needs_calibration'],
            ];
        }

        return $recommendations;
    }

    /**
     * Check for alerts based on reading.
     */
    private function checkForAlerts(Sensor $sensor, SensorReading $reading): array
    {
        $alerts = [];
        $thresholdStatus = $sensor->exceedsThresholds($reading->value);

        if ($thresholdStatus !== 'normal') {
            $alerts[] = SensorAlert::create([
                'sensor_id' => $sensor->id,
                'alert_type' => $thresholdStatus === 'below_min' ? AlertType::THRESHOLD_LOW : AlertType::THRESHOLD_HIGH,
                'severity' => AlertSeverity::WARNING,
                'message' => "Sensor {$sensor->name} value {$reading->formatted_value} exceeds threshold",
                'trigger_value' => $reading->value,
                'threshold_value' => $thresholdStatus === 'below_min' ? $sensor->threshold_min : $sensor->threshold_max,
                'triggered_at' => $reading->timestamp,
                'acknowledged' => false,
            ]);
        }

        // Check for anomalous readings
        if ($reading->isAnomalous()) {
            $alerts[] = SensorAlert::create([
                'sensor_id' => $sensor->id,
                'alert_type' => AlertType::ANOMALY,
                'severity' => AlertSeverity::INFO,
                'message' => "Anomalous reading detected from sensor {$sensor->name}",
                'trigger_value' => $reading->value,
                'triggered_at' => $reading->timestamp,
                'acknowledged' => false,
            ]);
        }

        // Check for poor quality
        if ($reading->quality < 0.5) {
            $alerts[] = SensorAlert::create([
                'sensor_id' => $sensor->id,
                'alert_type' => AlertType::QUALITY,
                'severity' => AlertSeverity::WARNING,
                'message' => "Poor data quality from sensor {$sensor->name}",
                'trigger_value' => $reading->quality,
                'triggered_at' => $reading->timestamp,
                'acknowledged' => false,
            ]);
        }

        return $alerts;
    }
}
