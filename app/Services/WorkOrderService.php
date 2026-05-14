<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\WorkOrder;

class WorkOrderService
{
    public function create(array $data): WorkOrder
    {
        $data['work_order_number'] = 'WO-' . now()->format('Ymd') . '-' . strtoupper(substr(str_replace('.', '', uniqid('', true)), -8));
        $data['created_by'] = auth()->id();

        return WorkOrder::create($data);
    }

    public function update(WorkOrder $workOrder, array $data): WorkOrder
    {
        $workOrder->update($data);

        return $workOrder;
    }

    public function changeStatus(WorkOrder $workOrder, string $newStatus): WorkOrder
    {
        $oldStatus = $workOrder->status;

        if ($newStatus === 'In Progress' && !$workOrder->started_date) {
            $workOrder->started_date = now();
        }

        if ($newStatus === 'Completed' && !$workOrder->completed_date) {
            $workOrder->completed_date = now();
        }

        $workOrder->status = $newStatus;
        $workOrder->save();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'work_order.status_changed',
            'model_type' => WorkOrder::class,
            'model_id' => $workOrder->id,
            'changes' => [
                'from' => $oldStatus,
                'to' => $newStatus,
            ],
        ]);

        return $workOrder;
    }

    public function getByAsset(Asset $asset, array $filters = [])
    {
        $query = $asset->workOrders();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->with(['asset', 'assignedTo'])
            ->orderBy('scheduled_date', 'asc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getOpenWorkOrders()
    {
        return WorkOrder::whereIn('status', ['Open', 'In Progress'])
            ->with(['asset', 'assignedTo'])
            ->orderBy('scheduled_date', 'asc')
            ->get();
    }

    public function getWorkOrderStats(): array
    {
        return [
            'open' => WorkOrder::where('status', 'Open')->count(),
            'in_progress' => WorkOrder::where('status', 'In Progress')->count(),
            'on_hold' => WorkOrder::where('status', 'On Hold')->count(),
            'completed' => WorkOrder::where('status', 'Completed')->count(),
            'total' => WorkOrder::count(),
        ];
    }
}
