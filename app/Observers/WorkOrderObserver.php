<?php

namespace App\Observers;

use App\Models\Asset;
use App\Models\WorkOrder;

class WorkOrderObserver
{
    /**
     * Sync the asset's status whenever a work order is created.
     */
    public function created(WorkOrder $workOrder): void
    {
        $this->syncAssetStatus($workOrder->asset_id);
    }

    /**
     * Sync the asset's status whenever a work order's status or asset changes.
     */
    public function updated(WorkOrder $workOrder): void
    {
        if (!$workOrder->wasChanged('status') && !$workOrder->wasChanged('asset_id')) {
            return;
        }

        $this->syncAssetStatus($workOrder->asset_id);

        // If the work order was reassigned to a different asset, sync the old one too
        if ($workOrder->wasChanged('asset_id')) {
            $this->syncAssetStatus($workOrder->getOriginal('asset_id'));
        }
    }

    /**
     * Sync the asset's status when a work order is deleted.
     */
    public function deleted(WorkOrder $workOrder): void
    {
        $this->syncAssetStatus($workOrder->asset_id);
    }

    /**
     * Determine and apply the correct asset status based on its open work orders.
     *
     * Rules:
     *   - Any WO in [in_progress, on_hold]  → asset becomes under_maintenance
     *   - No such WOs remaining              → asset reverts to active
     *   - Terminal/new asset states are never overwritten
     */
    private function syncAssetStatus(?string $assetId): void
    {
        if (!$assetId) {
            return;
        }

        $asset = Asset::find($assetId);
        if (!$asset) {
            return;
        }

        $sv = $asset->status instanceof \BackedEnum
            ? $asset->status->value
            : (string) $asset->status;

        // Never touch assets that are in a terminal or pre-deployment state
        if (in_array($sv, ['retired', 'disposed', 'ordered', 'received'])) {
            return;
        }

        $isActivelyBeingWorkedOn = WorkOrder::where('asset_id', $assetId)
            ->whereIn('status', ['in_progress', 'on_hold'])
            ->exists();

        $targetStatus = $isActivelyBeingWorkedOn ? 'under_maintenance' : 'active';

        if ($sv !== $targetStatus) {
            $asset->update([
                'status'     => $targetStatus,
                'updated_by' => auth()->id(),
            ]);
        }
    }
}
