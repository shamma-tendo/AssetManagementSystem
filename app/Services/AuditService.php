<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function logActivity(
        string $action,
        string $modelType = null,
        string $modelId = null,
        array $changes = null,
        string $description = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'changes' => $changes,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ]);
    }

    public function getActivityLog(array $filters = [])
    {
        $query = ActivityLog::query();

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['model_type'])) {
            $query->where('model_type', $filters['model_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function getUserAuditTrail(string $userId)
    {
        return ActivityLog::where('user_id', $userId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getModelAuditTrail(string $modelType, string $modelId)
    {
        return ActivityLog::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }
}
