<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'parent_category_id' => $this->parent_category_id,
            'pm_frequency_months' => $this->pm_frequency_months,
            'useful_life_years' => $this->useful_life_years,
            'depreciation_method' => $this->depreciation_method,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'parent' => $this->when($this->parent, new CategoryResource($this->parent)),
            'children' => $this->when($this->relationLoaded('children'), CategoryResource::collection($this->children)),
            'assets_count' => $this->when(isset($this->assets_count), $this->assets_count),
            'has_children' => $this->when(isset($this->children_count), $this->children_count > 0),
            'can_delete' => $this->canDelete(),
            'maintenance_status' => $this->getMaintenanceStatus(),
        ];
    }

    /**
     * Determine if category can be deleted.
     */
    private function canDelete(): bool
    {
        return !$this->assets()->exists() && !$this->children()->exists();
    }

    /**
     * Get maintenance status for the category.
     */
    private function getMaintenanceStatus(): array
    {
        $activeAssets = $this->assets()->where('status', 'active')->count();
        
        if ($activeAssets === 0) {
            return [
                'status' => 'no_assets',
                'message' => 'No active assets in this category',
                'color' => 'gray',
            ];
        }

        // This is a simplified version - in production, you'd calculate based on actual maintenance dates
        $overdueCount = 0;
        $dueSoonCount = 0;
        
        if ($overdueCount > 0) {
            return [
                'status' => 'overdue',
                'message' => "{$overdueCount} assets overdue for maintenance",
                'color' => 'red',
            ];
        } elseif ($dueSoonCount > 0) {
            return [
                'status' => 'due_soon',
                'message' => "{$dueSoonCount} assets due for maintenance soon",
                'color' => 'yellow',
            ];
        } else {
            return [
                'status' => 'on_schedule',
                'message' => 'All assets on maintenance schedule',
                'color' => 'green',
            ];
        }
    }
}
