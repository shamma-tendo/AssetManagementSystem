<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AssetDocument;
use App\Models\AssetLoan;
use App\Models\AssetWarranty;
use App\Models\InsurancePolicy;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for managing household asset features
 */
class HouseholdAssetService
{
    /**
     * Create insurance policy for asset
     */
    public function createInsurancePolicy(
        string $organizationId,
        ?string $assetId,
        string $policyNumber,
        string $provider,
        float $coverageAmount,
        \DateTime $startDate,
        \DateTime $endDate,
        float $premiumAmount,
        string $premiumFrequency = 'annual',
        ?string $coverageDetails = null
    ): InsurancePolicy {
        $policy = InsurancePolicy::create([
            'organization_id' => $organizationId,
            'asset_id' => $assetId,
            'policy_number' => $policyNumber,
            'provider' => $provider,
            'coverage_amount' => $coverageAmount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'premium_amount' => $premiumAmount,
            'premium_frequency' => $premiumFrequency,
            'coverage_details' => $coverageDetails,
            'is_active' => true,
        ]);

        // Alert if expiring soon
        if ($policy->daysUntilExpiration() < 30) {
            Alert::create([
                'organization_id' => $organizationId,
                'alert_type' => 'insurance_expiring',
                'title' => 'Insurance Policy Expiring Soon',
                'message' => "Policy {$policyNumber} expires in {$policy->daysUntilExpiration()} days",
                'severity' => 'high',
            ]);
        }

        return $policy;
    }

    /**
     * Track asset loan to friend/family
     */
    public function loanAsset(
        string $organizationId,
        string $assetId,
        string $borrowedBy,
        string $relationship,
        \DateTime $dueBackAt,
        ?string $borrowedByContact = null,
        ?string $conditionAtLoan = null
    ): AssetLoan {
        $loan = AssetLoan::create([
            'organization_id' => $organizationId,
            'asset_id' => $assetId,
            'borrowed_by' => $borrowedBy,
            'borrowed_by_contact' => $borrowedByContact,
            'relationship' => $relationship,
            'loaned_at' => now(),
            'due_back_at' => $dueBackAt,
            'condition_at_loan' => $conditionAtLoan,
            'status' => 'active',
        ]);

        // Alert about loan
        Alert::create([
            'organization_id' => $organizationId,
            'asset_id' => $assetId,
            'alert_type' => 'asset_loaned',
            'title' => 'Asset Loaned Out',
            'message' => "Asset loaned to {$borrowedBy} ({$relationship}). Due back: {$dueBackAt->format('M d, Y')}",
            'severity' => 'low',
        ]);

        return $loan;
    }

    /**
     * Get overdue loans
     */
    public function getOverdueLoans(string $organizationId): Collection
    {
        return AssetLoan::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->whereDate('due_back_at', '<', now())
            ->with(['asset'])
            ->get();
    }

    /**
     * Record loan return
     */
    public function returnLoan(
        AssetLoan $loan,
        string $conditionAtReturn,
        ?string $notes = null
    ): AssetLoan {
        $loan->returnAsset($conditionAtReturn, $notes);

        Alert::create([
            'organization_id' => $loan->organization_id,
            'asset_id' => $loan->asset_id,
            'alert_type' => 'asset_returned',
            'title' => 'Loaned Asset Returned',
            'message' => "Asset borrowed by {$loan->borrowed_by} has been returned. Condition: {$conditionAtReturn}",
            'severity' => 'low',
        ]);

        return $loan;
    }

    /**
     * Schedule maintenance for asset
     */
    public function scheduleMaintenance(
        string $organizationId,
        string $assetId,
        string $serviceType,
        \DateTime $nextServiceDate,
        ?int $intervalDays = null,
        ?string $serviceProvider = null,
        ?float $estimatedCost = null,
        ?string $notes = null
    ): MaintenanceSchedule {
        $schedule = MaintenanceSchedule::create([
            'organization_id' => $organizationId,
            'asset_id' => $assetId,
            'service_type' => $serviceType,
            'next_service_date' => $nextServiceDate,
            'service_interval_days' => $intervalDays,
            'service_provider' => $serviceProvider,
            'estimated_cost' => $estimatedCost,
            'notes' => $notes,
        ]);

        return $schedule;
    }

    /**
     * Get maintenance items due soon
     */
    public function getDueMaintenanceItems(string $organizationId, int $daysAhead = 7): Collection
    {
        return MaintenanceSchedule::where('organization_id', $organizationId)
            ->whereBetween('next_service_date', [now(), now()->addDays($daysAhead)])
            ->with(['asset'])
            ->orderBy('next_service_date')
            ->get();
    }

    /**
     * Upload document for asset
     */
    public function uploadDocument(
        string $organizationId,
        string $assetId,
        string $documentType, // receipt, warranty, photo, certificate, manual, other
        string $filePath,
        string $fileName,
        string $fileSize,
        User $uploadedBy,
        ?\DateTime $documentDate = null,
        ?string $notes = null
    ): AssetDocument {
        return AssetDocument::create([
            'organization_id' => $organizationId,
            'asset_id' => $assetId,
            'document_type' => $documentType,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'document_date' => $documentDate,
            'notes' => $notes,
            'uploaded_by' => $uploadedBy->id,
        ]);
    }

    /**
     * Add warranty for asset
     */
    public function addWarranty(
        string $organizationId,
        string $assetId,
        string $warrantyType,
        \DateTime $startDate,
        \DateTime $endDate,
        ?string $coverageDetails = null,
        ?string $providerName = null,
        ?string $providerContact = null
    ): AssetWarranty {
        $warranty = AssetWarranty::create([
            'organization_id' => $organizationId,
            'asset_id' => $assetId,
            'warranty_type' => $warrantyType,
            'warranty_start_date' => $startDate,
            'warranty_end_date' => $endDate,
            'coverage_details' => $coverageDetails,
            'provider_name' => $providerName,
            'provider_contact' => $providerContact,
        ]);

        // Alert if expiring soon
        if ($warranty->daysUntilExpiration() < 30) {
            Alert::create([
                'organization_id' => $organizationId,
                'asset_id' => $assetId,
                'alert_type' => 'warranty_expiring',
                'title' => 'Warranty Expiring Soon',
                'message' => "{$warrantyType} warranty expires in {$warranty->daysUntilExpiration()} days",
                'severity' => 'medium',
            ]);
        }

        return $warranty;
    }

    /**
     * File warranty claim
     */
    public function claimWarranty(
        AssetWarranty $warranty,
        string $claimNotes
    ): AssetWarranty {
        return $warranty->claimWarranty($claimNotes);
    }

    /**
     * Get asset documents by type
     */
    public function getAssetDocumentsByType(
        string $assetId,
        string $documentType
    ): Collection {
        return AssetDocument::where('asset_id', $assetId)
            ->where('document_type', $documentType)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
