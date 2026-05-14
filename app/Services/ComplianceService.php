<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Inspection;
use App\Models\Asset;

class ComplianceService
{
    public function scheduleInspection(array $data): Inspection
    {
        return Inspection::create($data);
    }

    public function completeInspection(Inspection $inspection, array $data): Inspection
    {
        $data['completed_date'] = now();
        $inspection->update($data);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'inspection.completed',
            'model_type' => Inspection::class,
            'model_id' => $inspection->id,
        ]);

        return $inspection;
    }

    public function getUpcomingInspections(int $days = 7)
    {
        $until = now()->addDays($days);

        return Inspection::query()
            ->whereNotIn('status', ['Completed', 'Passed'])
            ->where(function ($q) use ($until) {
                $q->whereBetween('scheduled_date', [now(), $until])
                    ->orWhere(function ($q2) use ($until) {
                        $q2->whereNotNull('next_due_date')
                            ->whereBetween('next_due_date', [now(), $until]);
                    });
            })
            ->with(['asset', 'inspector'])
            ->orderBy('scheduled_date', 'asc')
            ->get();
    }

    public function getOverdueInspections()
    {
        return Inspection::query()
            ->whereNotIn('status', ['Completed', 'Passed'])
            ->where(function ($q) {
                $q->where('scheduled_date', '<', now())
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('next_due_date')
                            ->where('next_due_date', '<', now());
                    });
            })
            ->with(['asset', 'inspector'])
            ->orderBy('scheduled_date', 'desc')
            ->get();
    }

    public function getAssetInspections(Asset $asset, array $filters = [])
    {
        $query = $asset->inspections();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('inspection_type', $filters['type']);
        }

        return $query->with(['inspector'])
            ->orderBy('scheduled_date', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getComplianceStats(): array
    {
        return [
            'total_inspections' => Inspection::count(),
            'scheduled' => Inspection::where('status', 'Scheduled')->count(),
            'completed' => Inspection::where('status', 'Completed')->count(),
            'overdue' => Inspection::query()
                ->whereNotIn('status', ['Completed', 'Passed'])
                ->where(function ($q) {
                    $q->where('scheduled_date', '<', now())
                        ->orWhere(function ($q2) {
                            $q2->whereNotNull('next_due_date')
                                ->where('next_due_date', '<', now());
                        });
                })
                ->count(),
            'compliance_met' => Inspection::where('compliance_met', true)->count(),
            'compliance_failed' => Inspection::where('compliance_met', false)->count(),
        ];
    }
}
