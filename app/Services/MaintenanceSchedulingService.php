<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\WorkOrder;
use App\Models\MaintenanceHistory;
use App\Models\User;
use App\Models\UserRole;
use App\Models\WorkOrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaintenanceSchedulingService
{
    /**
     * Process automated scheduling for all active schedules.
     */
    public function processAutomatedScheduling(): array
    {
        $results = [
            'processed' => 0,
            'work_orders_created' => 0,
            'overdue_found' => 0,
            'due_soon_found' => 0,
            'errors' => [],
        ];

        try {
            // Get all active schedules that auto-create work orders
            $schedules = MaintenanceSchedule::active()
                ->autoCreate()
                ->where('next_due_date', '<=', now()->addDays(7)) // Look ahead 7 days
                ->get();

            foreach ($schedules as $schedule) {
                try {
                    $this->processIndividualSchedule($schedule, $results);
                } catch (\Exception $e) {
                    $results['errors'][] = "Error processing schedule {$schedule->id}: " . $e->getMessage();
                    Log::error("Error processing maintenance schedule", [
                        'schedule_id' => $schedule->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update statistics
            $results['overdue_found'] = MaintenanceSchedule::overdue()->count();
            $results['due_soon_found'] = MaintenanceSchedule::dueSoon()->count();

        } catch (\Exception $e) {
            Log::error("Error in automated scheduling process", [
                'error' => $e->getMessage(),
            ]);
            $results['errors'][] = "System error: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Process an individual maintenance schedule.
     */
    private function processIndividualSchedule(MaintenanceSchedule $schedule, array &$results): void
    {
        $results['processed']++;

        // Check if work order already exists for this due date
        $existingWorkOrder = WorkOrder::where('maintenance_schedule_id', $schedule->id)
            ->where('scheduled_date', $schedule->next_due_date)
            ->whereNotIn('status', ['cancelled', 'closed'])
            ->first();

        if ($existingWorkOrder) {
            return; // Skip if work order already exists
        }

        // Determine if we should create work order based on urgency
        $daysUntilDue = $schedule->days_until_due;
        $shouldCreate = false;

        if ($daysUntilDue < 0) {
            // Overdue - create immediately
            $shouldCreate = true;
            $priority = 'urgent';
        } elseif ($daysUntilDue <= 7) {
            // Due within 7 days - create
            $shouldCreate = true;
            $priority = $daysUntilDue <= 2 ? 'high' : 'normal';
        } elseif ($schedule->frequency_type === 'daily' || $schedule->frequency_type === 'hourly') {
            // High frequency schedules - create 1 day in advance
            $shouldCreate = $daysUntilDue <= 1;
            $priority = 'normal';
        }

        if ($shouldCreate) {
            $this->createAutomatedWorkOrder($schedule, $priority, $results);
        }
    }

    /**
     * Create an automated work order from a maintenance schedule.
     */
    private function createAutomatedWorkOrder(MaintenanceSchedule $schedule, string $priority, array &$results): void
    {
        DB::beginTransaction();
        try {
            $workOrder = WorkOrder::create([
                'title' => $schedule->title,
                'description' => $schedule->description,
                'priority' => $priority,
                'type' => 'preventive_maintenance',
                'asset_id' => $schedule->asset_id,
                'assigned_to' => $schedule->assigned_technician_id,
                'estimated_hours' => $schedule->estimated_duration_hours,
                'estimated_cost' => $schedule->estimated_cost,
                'scheduled_date' => $schedule->next_due_date,
                'parts_used' => $schedule->required_parts,
                'tools_used' => $schedule->required_tools,
                'safety_precautions' => $schedule->safety_requirements,
                'created_by' => 1, // System user
                'maintenance_schedule_id' => $schedule->id,
                'status' => 'scheduled',
            ]);

            // Create maintenance history entry
            MaintenanceHistory::create([
                'maintenance_schedule_id' => $schedule->id,
                'work_order_id' => $workOrder->id,
                'asset_id' => $schedule->asset_id,
                'performed_by' => 1, // System user
                'performed_date' => now(),
                'completion_status' => 'scheduled',
                'created_by' => 1,
            ]);

            $results['work_orders_created']++;

            Log::info('Automated work order created', [
                'schedule_id' => $schedule->id,
                'work_order_id' => $workOrder->id,
                'priority' => $priority,
                'due_date' => $schedule->next_due_date,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate maintenance schedules for assets based on category defaults.
     */
    public function generateSchedulesForAsset(int $assetId, array $options = []): array
    {
        $results = [
            'schedules_created' => 0,
            'schedules_updated' => 0,
            'errors' => [],
        ];

        try {
            $asset = \App\Models\Asset::with('category')->findOrFail($assetId);
            $category = $asset->category;

            if (!$category) {
                $results['errors'][] = "Asset has no category defined";
                return $results;
            }

            // Default maintenance types for this category
            $defaultSchedules = $this->getDefaultSchedulesForCategory($category);

            foreach ($defaultSchedules as $scheduleData) {
                try {
                    $this->createOrUpdateSchedule($asset, $scheduleData, $options, $results);
                } catch (\Exception $e) {
                    $results['errors'][] = "Error creating schedule for {$scheduleData['title']}: " . $e->getMessage();
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = "Error generating schedules: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Get default schedules for a category.
     */
    private function getDefaultSchedulesForCategory($category): array
    {
        $schedules = [];

        // Preventive maintenance based on PM frequency
        if ($category->pm_frequency_months) {
            $schedules[] = [
                'title' => "Preventive Maintenance - {$category->name}",
                'description' => "Routine preventive maintenance for {$category->name} assets",
                'maintenance_type' => 'preventive',
                'frequency_type' => 'monthly',
                'frequency_months' => $category->pm_frequency_months,
                'work_order_priority' => 'normal',
                'auto_create_work_order' => true,
            ];
        }

        // Inspection based on useful life
        if ($category->useful_life_years) {
            $inspectionFrequency = min($category->useful_life_years, 12); // Inspect at least annually
            $schedules[] = [
                'title' => "Annual Inspection - {$category->name}",
                'description' => "Annual inspection for {$category->name} assets",
                'maintenance_type' => 'inspection',
                'frequency_type' => 'yearly',
                'frequency_interval' => 1,
                'work_order_priority' => 'normal',
                'auto_create_work_order' => true,
            ];
        }

        // Add category-specific schedules
        switch (strtolower($category->name)) {
            case 'vehicles':
            case 'cars':
            case 'trucks':
                $schedules[] = [
                    'title' => "Oil Change - Vehicle",
                    'description' => "Regular oil change for vehicle",
                    'maintenance_type' => 'routine',
                    'frequency_type' => 'monthly',
                    'frequency_months' => 3,
                    'work_order_priority' => 'normal',
                    'auto_create_work_order' => true,
                ];
                break;

            case 'servers':
                $schedules[] = [
                    'title' => "Server Maintenance",
                    'description' => "Routine server maintenance and updates",
                    'maintenance_type' => 'routine',
                    'frequency_type' => 'monthly',
                    'frequency_months' => 1,
                    'work_order_priority' => 'high',
                    'auto_create_work_order' => true,
                ];
                break;

            case 'hvac':
                $schedules[] = [
                    'title' => "HVAC Filter Change",
                    'description' => "Replace HVAC filters",
                    'maintenance_type' => 'routine',
                    'frequency_type' => 'monthly',
                    'frequency_months' => 3,
                    'work_order_priority' => 'normal',
                    'auto_create_work_order' => true,
                ];
                break;
        }

        return $schedules;
    }

    /**
     * Create or update a maintenance schedule.
     */
    private function createOrUpdateSchedule($asset, array $scheduleData, array $options, array &$results): void
    {
        // Check if schedule already exists for this asset and type
        $existingSchedule = MaintenanceSchedule::where('asset_id', $asset->id)
            ->where('title', $scheduleData['title'])
            ->first();

        if ($existingSchedule) {
            // Update existing schedule
            $existingSchedule->update([
                'frequency_months' => $scheduleData['frequency_months'] ?? $existingSchedule->frequency_months,
                'frequency_interval' => $scheduleData['frequency_interval'] ?? $existingSchedule->frequency_interval,
                'next_due_date' => $this->calculateNextDueDate($existingSchedule, $asset->purchase_date),
                'updated_by' => auth()->id(),
            ]);
            $results['schedules_updated']++;
        } else {
            // Create new schedule
            $nextDueDate = $this->calculateNextDueDateFromPurchase($scheduleData, $asset->purchase_date);
            
            MaintenanceSchedule::create([
                'asset_id' => $asset->id,
                'title' => $scheduleData['title'],
                'description' => $scheduleData['description'],
                'maintenance_type' => $scheduleData['maintenance_type'],
                'frequency_type' => $scheduleData['frequency_type'],
                'frequency_months' => $scheduleData['frequency_months'] ?? null,
                'frequency_interval' => $scheduleData['frequency_interval'] ?? 1,
                'next_due_date' => $nextDueDate,
                'due_date_based_on' => $asset->purchase_date,
                'work_order_priority' => $scheduleData['work_order_priority'],
                'auto_create_work_order' => $scheduleData['auto_create_work_order'],
                'created_by' => auth()->id(),
            ]);
            $results['schedules_created']++;
        }
    }

    /**
     * Calculate next due date for a schedule.
     */
    private function calculateNextDueDate($schedule, $purchaseDate): Carbon
    {
        $baseDate = $schedule->last_performed_date ?? $purchaseDate;
        
        return match($schedule->frequency_type) {
            'daily' => $baseDate->addDays($schedule->frequency_days ?? 1),
            'weekly' => $baseDate->addWeeks($schedule->frequency_interval),
            'monthly' => $baseDate->addMonths($schedule->frequency_months),
            'yearly' => $baseDate->addYears($schedule->frequency_interval),
            'hourly' => $baseDate->addHours($schedule->frequency_hours),
            default => $baseDate->addMonths(1),
        };
    }

    /**
     * Calculate next due date from purchase date.
     */
    private function calculateNextDueDateFromPurchase(array $scheduleData, $purchaseDate): Carbon
    {
        return match($scheduleData['frequency_type']) {
            'daily' => $purchaseDate->addDays($scheduleData['frequency_days'] ?? 1),
            'weekly' => $purchaseDate->addWeeks($scheduleData['frequency_interval']),
            'monthly' => $purchaseDate->addMonths($scheduleData['frequency_months']),
            'yearly' => $purchaseDate->addYears($scheduleData['frequency_interval']),
            'hourly' => $purchaseDate->addHours($scheduleData['frequency_hours']),
            default => $purchaseDate->addMonths(1),
        };
    }

    /**
     * Get maintenance compliance report.
     */
    public function getComplianceReport(array $filters = []): array
    {
        $query = MaintenanceHistory::with(['asset', 'performer']);

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->whereDate('performed_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('performed_date', '<=', $filters['date_to']);
        }
        if (isset($filters['asset_id'])) {
            $query->where('asset_id', $filters['asset_id']);
        }
        if (isset($filters['performed_by'])) {
            $query->where('performed_by', $filters['performed_by']);
        }

        $history = $query->get();

        return [
            'total_maintenance' => $history->count(),
            'completed_on_time' => $history->where('completed_on_time', true)->count(),
            'overdue_count' => $history->where('completed_on_time', false)->count(),
            'compliance_rate' => $history->count() > 0 ? 
                ($history->where('completed_on_time', true)->count() / $history->count()) * 100 : 100,
            'average_performance_rating' => $history->whereNotNull('performance_rating')->avg('performance_rating'),
            'total_cost' => $history->sum('actual_cost'),
            'total_hours' => $history->sum('actual_duration_hours'),
            'by_maintenance_type' => $history->groupBy('maintenance_schedule.maintenance_type')
                ->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'compliance_rate' => $group->where('completed_on_time', true)->count() / $group->count() * 100,
                        'avg_cost' => $group->avg('actual_cost'),
                        'avg_hours' => $group->avg('actual_duration_hours'),
                    ];
                }),
            'by_performer' => $history->groupBy('performed_by')
                ->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'compliance_rate' => $group->where('completed_on_time', true)->count() / $group->count() * 100,
                        'avg_performance' => $group->whereNotNull('performance_rating')->avg('performance_rating'),
                    ];
                }),
        ];
    }

    /**
     * Get upcoming maintenance summary.
     */
    public function getUpcomingMaintenanceSummary(int $days = 30): array
    {
        $schedules = MaintenanceSchedule::active()
            ->with(['asset', 'assignedTechnician'])
            ->whereBetween('next_due_date', [now(), now()->addDays($days)])
            ->orderBy('next_due_date')
            ->get();

        return [
            'total_upcoming' => $schedules->count(),
            'overdue_count' => $schedules->where('isOverdue', true)->count(),
            'due_today_count' => $schedules->filter(fn($s) => $s->next_due_date->isToday())->count(),
            'due_this_week' => $schedules->filter(fn($s) => $s->next_due_date->between(now(), now()->endOfWeek()))->count(),
            'by_priority' => $schedules->groupBy('priority_level')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'by_maintenance_type' => $schedules->groupBy('maintenance_type.value')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'by_technician' => $schedules->whereNotNull('assigned_technician_id')
                ->groupBy('assignedTechnician.full_name')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'schedules' => $schedules->take(10), // Top 10 for preview
        ];
    }
}
