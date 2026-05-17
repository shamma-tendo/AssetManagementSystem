<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceSchedule;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MaintenanceScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key constraints temporarily for seeding
        \DB::statement('PRAGMA foreign_keys = OFF');
        
        $assets = Asset::all();
        $users = User::all();
        
        if ($assets->isEmpty() || $users->isEmpty()) {
            $this->command->info('No assets or users found. Please run AssetSeeder and UserSeeder first.');
            return;
        }

        $schedules = [];
        $batchSize = 100;
        $totalSchedules = 2000;

        // Maintenance types and their characteristics
        $maintenanceTypes = [
            'preventive' => [
                'priority_distribution' => ['low' => 30, 'normal' => 50, 'high' => 15, 'urgent' => 5],
                'frequency_days' => [30, 90],
                'condition_range' => [70, 95]
            ],
            'corrective' => [
                'priority_distribution' => ['low' => 10, 'normal' => 30, 'high' => 40, 'urgent' => 20],
                'frequency_days' => [60, 180],
                'condition_range' => [40, 80]
            ],
            'predictive' => [
                'priority_distribution' => ['low' => 20, 'normal' => 40, 'high' => 30, 'urgent' => 10],
                'frequency_days' => [45, 120],
                'condition_range' => [50, 85]
            ],
            'emergency' => [
                'priority_distribution' => ['low' => 0, 'normal' => 10, 'high' => 30, 'urgent' => 60],
                'frequency_days' => [1, 7],
                'condition_range' => [10, 50]
            ]
        ];

        for ($i = 1; $i <= $totalSchedules; $i++) {
            $asset = $assets->random();
            $type = array_rand($maintenanceTypes);
            $typeConfig = $maintenanceTypes[$type];
            
            $priority = $this->getWeightedPriority($typeConfig['priority_distribution']);
            $frequencyDays = rand($typeConfig['frequency_days'][0], $typeConfig['frequency_days'][1]);
            $dueDate = Carbon::now()->addDays(rand(-30, $frequencyDays));
            $conditionScore = rand($typeConfig['condition_range'][0], $typeConfig['condition_range'][1]);
            
            // Determine status based on due date
            if ($dueDate < now()) {
                $status = 'overdue';
            } elseif ($dueDate <= now()->addDays(7)) {
                $status = 'due_soon';
            } elseif ($dueDate <= now()->addDays(30)) {
                $status = 'scheduled';
            } else {
                $status = 'pending';
            }

            $creator = $users->random();
            $assignedTo = ($status !== 'pending') ? $users->random() : null;

            $schedule = [
                'id' => Str::uuid(),
                'asset_id' => $asset->id,
                'title' => $this->generateScheduleTitle($type, $asset),
                'description' => $this->generateScheduleDescription($type, $asset),
                'maintenance_type' => $type,
                'work_order_priority' => $priority,
                'is_active' => true,
                'next_due_date' => $dueDate,
                'assigned_technician_id' => $assignedTo?->id,
                'created_by' => $creator->id,
                'estimated_duration_hours' => rand(2, 16),
                'estimated_cost' => rand(100, 2000),
                'frequency_days' => $frequencyDays,
                'last_performed_date' => $dueDate->copy()->subDays($frequencyDays),
                'next_due_date' => $dueDate->copy()->addDays($frequencyDays),
                'required_parts' => $this->generateRequiredParts($type),
                'required_tools' => $this->generateRequiredTools($type),
                'safety_requirements' => $this->generateSafetyRequirements($type),
                'created_at' => $dueDate->copy()->subDays(rand(1, 90)),
                'updated_at' => now(),
            ];

            $schedules[] = $schedule;

            // Insert in batches
            if (count($schedules) >= $batchSize) {
                MaintenanceSchedule::insert($schedules);
                $schedules = [];
            }
        }

        // Insert remaining schedules
        if (!empty($schedules)) {
            MaintenanceSchedule::insert($schedules);
        }
    }

    private function getWeightedPriority($distribution): string
    {
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($distribution as $priority => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $priority;
            }
        }

        return 'medium';
    }

    private function generateScheduleTitle($type, $asset): string
    {
        $titles = [
            'preventive' => [
                'Preventive Maintenance - ' . $asset->name,
                'Scheduled Inspection - ' . $asset->name,
                'Routine Service - ' . $asset->name,
                'Preventive Check - ' . $asset->name
            ],
            'corrective' => [
                'Corrective Maintenance - ' . $asset->name,
                'Repair Maintenance - ' . $asset->name,
                'Corrective Action - ' . $asset->name,
                'Fault Resolution - ' . $asset->name
            ],
            'predictive' => [
                'Predictive Maintenance - ' . $asset->name,
                'Condition-Based Maintenance - ' . $asset->name,
                'Predictive Service - ' . $asset->name,
                'Proactive Maintenance - ' . $asset->name
            ],
            'emergency' => [
                'Emergency Maintenance - ' . $asset->name,
                'Urgent Repair - ' . $asset->name,
                'Critical Maintenance - ' . $asset->name,
                'Emergency Service - ' . $asset->name
            ]
        ];

        return $titles[$type][array_rand($titles[$type])];
    }

    private function generateScheduleDescription($type, $asset): string
    {
        $descriptions = [
            'preventive' => 'Scheduled preventive maintenance to ensure optimal performance and prevent unexpected failures.',
            'corrective' => 'Corrective maintenance to address identified issues and restore equipment to proper working condition.',
            'predictive' => 'Predictive maintenance based on condition monitoring and performance analysis.',
            'emergency' => 'Emergency maintenance required to address critical failure and restore operation.'
        ];

        return $descriptions[$type] . ' Asset: ' . $asset->name . ' (Serial: ' . $asset->serial_number . ')';
    }

    private function generateScheduleNotes($type): string
    {
        $notes = [
            'preventive' => 'Follow manufacturer maintenance schedule. Check all safety interlocks.',
            'corrective' => 'Investigate root cause. Document findings for future reference.',
            'predictive' => 'Review sensor data and performance trends before maintenance.',
            'emergency' => 'Safety first. Lockout/tagout procedures required. Immediate attention needed.'
        ];

        return $notes[$type];
    }

    private function generateRequiredParts($type): string
    {
        $parts = [
            'preventive' => 'Filters, Lubricants, Gaskets, Seals, Fasteners',
            'corrective' => 'Replacement components, Fasteners, Gaskets, Testing equipment',
            'predictive' => 'Sensors, Calibration tools, Diagnostic equipment',
            'emergency' => 'Emergency replacement parts, Temporary fixes, Safety equipment'
        ];

        return $parts[$type];
    }

    private function generateRequiredTools($type): string
    {
        $tools = [
            'preventive' => 'Standard tool kit, Diagnostic equipment, Safety gear',
            'corrective' => 'Specialized tools, Diagnostic equipment, Replacement parts',
            'predictive' => 'Analysis tools, Calibration equipment, Monitoring devices',
            'emergency' => 'Emergency tools, Safety equipment, Temporary fix materials'
        ];

        return $tools[$type];
    }

    private function generateSafetyRequirements($type): string
    {
        $safety = [
            'preventive' => 'Lockout/tagout, PPE required, Follow safety procedures',
            'corrective' => 'Lockout/tagout, Safety barriers, Emergency stop tested',
            'predictive' => 'Standard safety procedures, Monitoring equipment safety',
            'emergency' => 'Full emergency protocols, Safety barriers, Emergency equipment ready'
        ];

        return $safety[$type];
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
