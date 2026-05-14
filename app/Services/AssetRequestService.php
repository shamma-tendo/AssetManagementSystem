<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AssetRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for managing asset requests from managers to executives
 */
class AssetRequestService
{
    /**
     * Create a new asset request
     */
    public function createRequest(
        string $organizationId,
        User $requestedBy,
        string $title,
        string $description,
        int $quantity,
        string $assetType,
        ?float $estimatedCost = null
    ): AssetRequest {
        $request = AssetRequest::create([
            'organization_id' => $organizationId,
            'requested_by' => $requestedBy->id,
            'title' => $title,
            'description' => $description,
            'quantity' => $quantity,
            'asset_type' => $assetType,
            'estimated_cost' => $estimatedCost,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Create alert for executives
        Alert::create([
            'organization_id' => $organizationId,
            'alert_type' => 'asset_request_pending',
            'title' => 'New Asset Request',
            'message' => "{$requestedBy->name} has submitted a request for {$quantity} {$assetType}(s).",
            'severity' => 'high',
        ]);

        return $request;
    }

    /**
     * Approve an asset request
     */
    public function approveRequest(
        AssetRequest $request,
        User $approver,
        ?string $notes = null
    ): AssetRequest {
        if (!$this->isAuthorizedApprover($approver)) {
            throw new \Exception('User is not authorized to approve requests');
        }

        $request->approve($approver, $notes);

        // Create notification alert
        Alert::create([
            'organization_id' => $request->organization_id,
            'alert_type' => 'asset_request_approved',
            'title' => 'Request Approved',
            'message' => "{$approver->name} approved the request for {$request->quantity} {$request->asset_type}(s).",
            'severity' => 'medium',
        ]);

        return $request;
    }

    /**
     * Reject an asset request
     */
    public function rejectRequest(
        AssetRequest $request,
        User $approver,
        string $notes
    ): AssetRequest {
        if (!$this->isAuthorizedApprover($approver)) {
            throw new \Exception('User is not authorized to reject requests');
        }

        $request->reject($approver, $notes);

        // Create notification alert
        Alert::create([
            'organization_id' => $request->organization_id,
            'alert_type' => 'asset_request_rejected',
            'title' => 'Request Rejected',
            'message' => "{$approver->name} rejected the request. Reason: {$notes}",
            'severity' => 'low',
        ]);

        return $request;
    }

    /**
     * Get pending requests for approval
     */
    public function getPendingRequests(string $organizationId): Collection
    {
        return AssetRequest::where('organization_id', $organizationId)
            ->where('status', 'pending')
            ->with(['requestedBy'])
            ->orderBy('requested_at', 'desc')
            ->get();
    }

    /**
     * Get all requests for an organization
     */
    public function getOrganizationRequests(string $organizationId): Collection
    {
        return AssetRequest::where('organization_id', $organizationId)
            ->with(['requestedBy', 'approvedBy'])
            ->orderBy('requested_at', 'desc')
            ->get();
    }

    /**
     * Get requests for a specific user
     */
    public function getUserRequests(User $user, string $organizationId): Collection
    {
        return AssetRequest::where('organization_id', $organizationId)
            ->where('requested_by', $user->id)
            ->orderBy('requested_at', 'desc')
            ->get();
    }

    /**
     * Check if user is authorized to approve requests
     */
    private function isAuthorizedApprover(User $user): bool
    {
        $role = strtolower($user->role->name ?? '');
        return in_array($role, ['ceo', 'cfo', 'admin', 'executive']);
    }
}
