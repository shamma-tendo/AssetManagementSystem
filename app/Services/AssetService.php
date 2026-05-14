<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Pagination\Paginator;

class AssetService
{
    public function create(array $data): Asset
    {
        $data['status'] = 'Ordered';
        $data['current_value'] = $data['purchase_cost'];
        $data['created_by'] = auth()->id();
        if (auth()->user()?->organization_id) {
            $data['organization_id'] = auth()->user()->organization_id;
        }

        return Asset::create($data);
    }

    public function update(Asset $asset, array $data): Asset
    {
        $data['updated_by'] = auth()->id();
        $asset->update($data);

        return $asset->fresh();
    }

    public function changeStatus(Asset $asset, string $newStatus): Asset
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'asset.status_changed',
            'model_type' => Asset::class,
            'model_id' => $asset->id,
            'changes' => [
                'from' => $asset->status,
                'to' => $newStatus,
            ],
        ]);

        $asset->update([
            'status' => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        return $asset->fresh();
    }

    public function getAllAssets(array $filters = [])
    {
        $query = Asset::query();

        if (auth()->user()?->organization_id) {
            $query->where('organization_id', auth()->user()->organization_id);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('serial_number', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('barcode', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->with(['category', 'location', 'department'])
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getAssetStats(): array
    {
        $q = Asset::query();
        if (auth()->user()?->organization_id) {
            $q->where('organization_id', auth()->user()->organization_id);
        }

        return [
            'total_assets' => (clone $q)->count(),
            'active_assets' => (clone $q)->where('status', 'Active')->count(),
            'under_maintenance' => (clone $q)->where('status', 'Under Maintenance')->count(),
            'retired_assets' => (clone $q)->where('status', 'Retired')->count(),
            'total_asset_value' => (clone $q)->sum('current_value'),
        ];
    }
}
