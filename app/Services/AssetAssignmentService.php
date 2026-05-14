<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AssetAssignment;
use App\Models\AssetConditionReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for managing asset assignments and employee workflows
 */
class AssetAssignmentService
{
    /**
     * Assign asset to employee
     */
    public function assignToEmployee(
        string $assetId,
        string $organizationId,
        string $employeeId,
        User $assignedBy,
        ?string $notes = null,
        int $quantity = 1
    ): AssetAssignment {
        $assignment = AssetAssignment::create([
            'asset_id' => $assetId,
            'organization_id' => $organizationId,
            'assigned_to' => $employeeId,
            'assigned_by' => $assignedBy->id,
            'quantity' => $quantity,
            'status' => 'assigned',
            'assignment_notes' => $notes,
            'assigned_at' => now(),
        ]);

        // Create alert for the assigned employee
        Alert::create([
            'organization_id' => $organizationId,
            'asset_id' => $assetId,
            'alert_type' => 'asset_assigned',
            'title' => 'New Asset Assignment',
            'message' => "You have been assigned a new asset. Please log in to confirm receipt.",
            'severity' => 'medium',
        ]);

        return $assignment;
    }

    /**
     * Employee confirms receipt of assigned asset
     */
    public function confirmReceipt(AssetAssignment $assignment, User $employee): AssetAssignment
    {
        if ($assignment->assigned_to !== $employee->id) {
            throw new \Exception('Unauthorized action');
        }

        $assignment->confirmReceipt();

        Alert::create([
            'organization_id' => $assignment->organization_id,
            'asset_id' => $assignment->asset_id,
            'alert_type' => 'asset_acknowledged',
            'title' => 'Asset Receipt Confirmed',
            'message' => "{$employee->name} has confirmed receipt of the assigned asset.",
            'severity' => 'low',
        ]);

        return $assignment;
    }

    /**
     * Employee reports condition of their asset
     */
    public function reportCondition(
        AssetAssignment $assignment,
        User $employee,
        string $condition,
        ?string $description = null,
        ?string $actionRequired = null
    ): AssetConditionReport {
        if ($assignment->assigned_to !== $employee->id) {
            throw new \Exception('Unauthorized action');
        }

        $report = AssetConditionReport::create([
            'asset_assignment_id' => $assignment->id,
            'organization_id' => $assignment->organization_id,
            'reported_by' => $employee->id,
            'condition' => $condition,
            'description' => $description,
            'action_required' => $actionRequired,
            'requires_urgent_attention' => in_array($condition, ['broken', 'stolen', 'lost']),
            'reported_at' => now(),
        ]);

        // Create alert if urgent
        if ($report->requires_urgent_attention) {
            Alert::create([
                'organization_id' => $assignment->organization_id,
                'asset_id' => $assignment->asset_id,
                'alert_type' => 'asset_damaged',
                'title' => "URGENT: Asset Status Alert",
                'message' => "{$employee->name} reported: {$condition}. Immediate attention required.",
                'severity' => 'critical',
            ]);
        }

        return $report;
    }

    /**
     * Retrieve assignments for an employee
     */
    public function getEmployeeAssignments(User $employee, string $organizationId): Collection
    {
        return AssetAssignment::where('assigned_to', $employee->id)
            ->where('organization_id', $organizationId)
            ->with(['asset', 'conditionReports'])
            ->orderBy('assigned_at', 'desc')
            ->get();
    }

    /**
     * Get pending assignments needing acknowledgment
     */
    public function getPendingAssignments(User $employee, string $organizationId): Collection
    {
        return AssetAssignment::where('assigned_to', $employee->id)
            ->where('organization_id', $organizationId)
            ->where('status', 'assigned')
            ->with(['asset', 'assignedBy'])
            ->get();
    }

    /**
     * Return asset from employee
     */
    public function returnAsset(AssetAssignment $assignment, User $employee): AssetAssignment
    {
        if ($assignment->assigned_to !== $employee->id) {
            throw new \Exception('Unauthorized action');
        }

        return $assignment->markAsReturned();
    }
}
