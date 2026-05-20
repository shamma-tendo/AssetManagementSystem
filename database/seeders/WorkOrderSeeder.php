<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkOrder;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WorkOrderSeeder extends Seeder
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

        $workOrders = [];
        $batchSize = 100;
        $totalWorkOrders = 800;

        // Work order types and their characteristics
        $workOrderTypes = [
            'preventive_maintenance' => [
                'priority_distribution' => ['low' => 30, 'normal' => 50, 'high' => 15, 'urgent' => 5],
                'estimated_hours_range' => [2, 8],
                'completion_rate' => 0.85
            ],
            'corrective_maintenance' => [
                'priority_distribution' => ['low' => 10, 'normal' => 30, 'high' => 40, 'urgent' => 20],
                'estimated_hours_range' => [4, 16],
                'completion_rate' => 0.75
            ],
            'emergency_maintenance' => [
                'priority_distribution' => ['low' => 0, 'normal' => 10, 'high' => 30, 'urgent' => 60],
                'estimated_hours_range' => [1, 24],
                'completion_rate' => 0.90
            ],
            'inspection' => [
                'priority_distribution' => ['low' => 40, 'normal' => 40, 'high' => 15, 'urgent' => 5],
                'estimated_hours_range' => [1, 4],
                'completion_rate' => 0.95
            ],
            'calibration' => [
                'priority_distribution' => ['low' => 20, 'normal' => 50, 'high' => 25, 'urgent' => 5],
                'estimated_hours_range' => [2, 6],
                'completion_rate' => 0.90
            ]
        ];

        $statuses = ['requested', 'approved', 'assigned', 'scheduled', 'in_progress', 'on_hold', 'completed', 'closed', 'cancelled'];
        $statusDistribution = [
            'requested' => 10,
            'approved' => 15,
            'assigned' => 15,
            'scheduled' => 20,
            'in_progress' => 15,
            'on_hold' => 5,
            'completed' => 15,
            'closed' => 3,
            'cancelled' => 2
        ];

        for ($i = 1; $i <= $totalWorkOrders; $i++) {
            $asset = $assets->random();
            $type = array_rand($workOrderTypes);
            $typeConfig = $workOrderTypes[$type];
            
            $priority = $this->getWeightedPriority($typeConfig['priority_distribution']);
            $status = $this->getWeightedStatus($statusDistribution);
            
            $createdAt = Carbon::now()->subDays(rand(1, 365));
            $scheduledDate = $createdAt->copy()->addDays(rand(1, 30));
            $estimatedHours = rand($typeConfig['estimated_hours_range'][0], $typeConfig['estimated_hours_range'][1]);
            $estimatedCost = $estimatedHours * rand(50, 150);
            
            // Determine completion status
            $isCompleted = $status === 'completed';
            $actualHours = $isCompleted ? rand($estimatedHours * 0.8, $estimatedHours * 1.3) : null;
            $actualCost = $isCompleted ? $estimatedCost * rand(0.8, 1.4) : null;
            $completedAt = $isCompleted ? $createdAt->copy()->addDays(rand(1, 14)) : null;
            $closedAt = $isCompleted ? $completedAt->copy()->addDays(rand(0, 2)) : null;
            $startedAt = $status === 'in_progress' ? $createdAt->copy()->addDays(rand(0, 7)) : null;
            
            $creator = $users->random();
            $assignedTo = ($status !== 'pending' && $status !== 'cancelled') ? $users->random() : null;
            $requestedBy = $users->random();

            $workOrder = [
                'id' => Str::uuid(),
                'title' => $this->generateWorkOrderTitle($type, $asset),
                'description' => $this->generateWorkOrderDescription($type, $asset),
                'priority' => $priority,
                'status' => $status,
                'type' => $type,
                'asset_id' => $asset->id,
                'assigned_to' => $assignedTo?->id,
                'created_by' => $creator->id,
                'requested_by' => $requestedBy->id,
                'estimated_hours' => $estimatedHours,
                'actual_hours' => $actualHours,
                'estimated_cost' => $estimatedCost,
                'actual_cost' => $actualCost,
                'scheduled_date' => $scheduledDate,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'closed_at' => $closedAt,
                'location_id' => $asset->location_id,
                'department_id' => $asset->department_id,
                'notes' => $this->generateWorkOrderNotes($type),
                'completion_notes' => $isCompleted ? $this->generateCompletionNotes($type) : null,
                'work_performed' => $isCompleted ? $this->generateWorkPerformed($type) : null,
                'parts_used' => $isCompleted ? $this->generatePartsUsed($type) : null,
                'tools_used' => $this->generateToolsUsed($type),
                'safety_precautions' => $this->generateSafetyPrecautions($type),
                'follow_up_required' => rand(0, 1) ? true : false,
                'follow_up_date' => rand(0, 1) ? $completedAt?->copy()->addDays(rand(7, 30)) : null,
                'customer_satisfaction' => $isCompleted ? rand(3, 5) : null,
                'created_at' => $createdAt,
                'updated_at' => $isCompleted ? $completedAt : now(),
            ];

            $workOrders[] = $workOrder;

            // Insert in batches
            if (count($workOrders) >= $batchSize) {
                WorkOrder::insert($workOrders);
                $workOrders = [];
            }
        }

        // Insert remaining work orders
        if (!empty($workOrders)) {
            WorkOrder::insert($workOrders);
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

        return 'pending';
    }

    private function generateWorkOrderTitle($type, $asset): string
    {
        $titles = [
            'preventive_maintenance' => [
                'Preventive Maintenance - ' . $asset->name,
                'Scheduled Service - ' . $asset->name,
                'Routine Check - ' . $asset->name,
                'Preventive Inspection - ' . $asset->name
            ],
            'corrective_maintenance' => [
                'Corrective Maintenance - ' . $asset->name,
                'Repair Required - ' . $asset->name,
                'Fix Required - ' . $asset->name,
                'Corrective Action - ' . $asset->name,
                'Fault Resolution - ' . $asset->name
            ],
            'emergency_maintenance' => [
                'Emergency Repair - ' . $asset->name,
                'Urgent Fix Required - ' . $asset->name,
                'Critical Issue - ' . $asset->name,
                'Emergency Service - ' . $asset->name
            ],
            'inspection' => [
                'Inspection - ' . $asset->name,
                'Safety Inspection - ' . $asset->name,
                'Compliance Check - ' . $asset->name,
                'Visual Inspection - ' . $asset->name
            ],
            'calibration' => [
                'Calibration - ' . $asset->name,
                'Equipment Calibration - ' . $asset->name,
                'Sensor Calibration - ' . $asset->name,
                'Instrument Calibration - ' . $asset->name
            ]
        ];

        return $titles[$type][array_rand($titles[$type])];
    }

    private function generateWorkOrderDescription($type, $asset): string
    {
        $descriptions = [
            'preventive_maintenance' => 'Scheduled preventive maintenance to ensure optimal performance and prevent unexpected failures.',
            'corrective_maintenance' => 'Corrective maintenance to address identified issues and restore equipment to proper working condition.',
            'emergency_maintenance' => 'Emergency maintenance required to address critical failure and restore operation.',
            'inspection' => 'Scheduled inspection to verify equipment condition and compliance with safety standards.',
            'calibration' => 'Equipment calibration to ensure accuracy and proper operation of instruments and sensors.'
        ];

        return $descriptions[$type] . ' Asset: ' . $asset->name . ' (Serial: ' . $asset->serial_number . ')';
    }

    private function generateWorkOrderNotes($type): string
    {
        $notes = [
            'preventive_maintenance' => 'Follow manufacturer maintenance schedule. Check all safety interlocks.',
            'corrective_maintenance' => 'Investigate root cause. Document findings for future reference.',
            'emergency_maintenance' => 'Immediate response required. Safety first.',
            'inspection' => 'Verify all safety parameters. Document inspection findings.',
            'calibration' => 'Use calibrated reference equipment. Document calibration results.'
        ];

        return $notes[$type];
    }

    private function generateCompletionNotes($type): string
    {
        $notes = [
            'preventive_maintenance' => 'All preventive maintenance tasks completed successfully. Equipment ready for service.',
            'corrective_maintenance' => 'Issue resolved. Root cause identified and addressed. Equipment functioning normally.',
            'emergency_maintenance' => 'Emergency issue resolved. Equipment back in operation.',
            'inspection' => 'Inspection completed. All parameters within acceptable limits.',
            'calibration' => 'Calibration completed. Equipment now operating within specifications.'
        ];

        return $notes[$type];
    }

    private function generateWorkPerformed($type): string
    {
        $tasks = [
            'preventive_maintenance' => 'Inspected all components, lubricated moving parts, replaced filters, calibrated sensors.',
            'corrective_maintenance' => 'Replaced faulty components, adjusted settings, tested functionality, verified operation.',
            'emergency_maintenance' => 'Replaced failed components, performed emergency repairs, tested and verified operation.',
            'inspection' => 'Visual inspection completed, measurements taken, documentation updated.',
            'calibration' => 'Equipment calibrated using reference standards, adjustments made, verification completed.'
        ];

        return $tasks[$type];
    }

    private function generatePartsUsed($type): string
    {
        $parts = [
            'preventive_maintenance' => 'Filters x2, Lubricants, Gaskets, Seals',
            'corrective_maintenance' => 'Replacement components, Fasteners, Gaskets, Testing equipment',
            'emergency_maintenance' => 'Emergency replacement parts, Temporary fixes, Safety equipment',
            'inspection' => 'Inspection forms, Measurement tools, Documentation',
            'calibration' => 'Calibration standards, Reference equipment, Adjustment tools'
        ];

        return $parts[$type];
    }

    private function generateToolsUsed($type): string
    {
        $tools = [
            'preventive_maintenance' => 'Standard tool kit, Diagnostic equipment, Safety gear',
            'corrective_maintenance' => 'Specialized tools, Diagnostic equipment, Replacement parts',
            'emergency_maintenance' => 'Emergency tool kit, Safety equipment, Temporary repair tools',
            'inspection' => 'Inspection tools, Measurement devices, Safety equipment',
            'calibration' => 'Calibration tools, Reference standards, Testing equipment'
        ];

        return $tools[$type];
    }

    private function generateSafetyPrecautions($type): string
    {
        $precautions = [
            'preventive_maintenance' => 'Lockout/tagout, PPE required, Follow safety procedures',
            'corrective_maintenance' => 'Lockout/tagout, Safety barriers, Emergency stop tested',
            'emergency_maintenance' => 'Emergency procedures, Full safety protocols, Immediate response',
            'inspection' => 'Safety equipment required, Follow inspection protocols',
            'calibration' => 'Equipment isolation, Calibration safety procedures'
        ];

        return $precautions[$type];
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
