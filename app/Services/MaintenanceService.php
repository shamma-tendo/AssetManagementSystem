<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\WorkOrder;
use Carbon\Carbon;

class MaintenanceService
{
    /**
     * Generate work orders for maintenance schedules due within the next X days.
     */
    public function generateWorkOrdersFromSchedules(int $daysAhead = 7): int
    {
        $dueDate = Carbon::now()->addDays($daysAhead);
        $count = 0;

        // Get schedules that are due but haven't had a work order generated yet for this cycle
        $schedules = MaintenanceSchedule::where('next_service_date', '<=', $dueDate)
            ->where('is_reminder_sent', false)
            ->get();

        foreach ($schedules as $schedule) {
            // Create the preventive work order
            WorkOrder::create([
                'work_order_number' => 'PM-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
                'asset_id' => $schedule->asset_id,
                'type' => 'Preventive',
                'status' => 'Open',
                'description' => "Scheduled maintenance: {$schedule->service_type}. " . ($schedule->notes ?? ''),
                'scheduled_date' => $schedule->next_service_date,
                'estimated_cost' => $schedule->estimated_cost,
                'created_by' => null, // System generated
            ]);

            $schedule->update(['is_reminder_sent' => true]);
            $count++;
        }

        return $count;
    }

    /**
     * Update the maintenance schedule when a work order is completed.
     */
    public function updateScheduleOnCompletion(WorkOrder $workOrder): void
    {
        $schedule = MaintenanceSchedule::where('asset_id', $workOrder->asset_id)
            ->first(); // For simplicity, take the first matching asset schedule

        if ($schedule) {
            $schedule->markAsServiced($workOrder->assignedTo?->name);
        }
    }
}