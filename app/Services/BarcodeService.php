<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetScan;
use App\Models\User;

/**
 * Service for handling barcode/QR code scanning
 */
class BarcodeService
{
    /**
     * Process a barcode/QR code scan
     */
    public function processScan(
        string $organizationId,
        string $barcodeValue,
        string $scanType = 'verification', // checkin, checkout, verification, inventory_count, assignment
        ?User $scannedBy = null,
        ?string $location = null,
        ?string $deviceInfo = null,
        ?string $notes = null
    ): ?AssetScan {
        // Find asset by barcode
        $asset = Asset::where('barcode', $barcodeValue)
            ->orWhere('qr_code', $barcodeValue)
            ->orWhere('serial_number', $barcodeValue)
            ->first();

        if (!$asset || $asset->organization_id !== $organizationId) {
            return null; // Asset not found or unauthorized organization
        }

        // Record the scan
        $scan = AssetScan::create([
            'organization_id' => $organizationId,
            'asset_id' => $asset->id,
            'scanned_by' => $scannedBy?->id,
            'barcode_value' => $barcodeValue,
            'scan_type' => $scanType,
            'location' => $location,
            'device_info' => $deviceInfo,
            'notes' => $notes,
        ]);

        return $scan;
    }

    /**
     * Get scan history for asset
     */
    public function getScanHistory(string $assetId, int $limit = 50)
    {
        return AssetScan::where('asset_id', $assetId)
            ->with(['scannedBy'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get scan activity for organization
     */
    public function getOrganzationScanActivity(string $organizationId, int $limit = 100)
    {
        return AssetScan::where('organization_id', $organizationId)
            ->with(['asset', 'scannedBy'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate barcode value (for new assets)
     */
    public function generateBarcode(): string
    {
        return 'AST-' . strtoupper(uniqid());
    }

    /**
     * Verify asset ownership by barcode
     */
    public function verifyAssetOwnership(string $organizationId, string $barcodeValue): bool
    {
        return Asset::where('organization_id', $organizationId)
            ->where(function ($query) use ($barcodeValue) {
                $query->where('barcode', $barcodeValue)
                    ->orWhere('qr_code', $barcodeValue)
                    ->orWhere('serial_number', $barcodeValue);
            })
            ->exists();
    }

    /**
     * Get checkout/checkin status for asset
     */
    public function getAssetCheckStatus(string $assetId): array
    {
        $lastScan = AssetScan::where('asset_id', $assetId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastScan) {
            return [
                'status' => 'unknown',
                'last_scan_at' => null,
                'location' => null,
                'scanned_by' => null,
            ];
        }

        $isCheckedOut = $lastScan->scan_type === 'checkout';

        return [
            'status' => $isCheckedOut ? 'checked_out' : 'checked_in',
            'last_scan_at' => $lastScan->created_at,
            'location' => $lastScan->location,
            'scanned_by' => $lastScan->scannedBy?->name,
            'scan_type' => $lastScan->scan_type,
        ];
    }

    /**
     * Generate QR code image (requires package like 'simple-qrcode')
     * This is a placeholder - implement with actual QR library
     */
    public function generateQRCode(Asset $asset): string
    {
        // Example with 'simple-qrcode' package:
        // return \QrCode::format('png')
        //     ->size(200)
        //     ->generate($asset->qr_code ?? $asset->barcode);

        return url("/api/assets/{$asset->id}/barcode");
    }

    /**
     * Batch scan verification
     */
    public function batchVerifyAssets(string $organizationId, array $barcodes): array
    {
        $results = [
            'found' => [],
            'not_found' => [],
            'unauthorized' => [],
        ];

        foreach ($barcodes as $barcode) {
            $asset = Asset::where(function ($query) use ($barcode) {
                $query->where('barcode', $barcode)
                    ->orWhere('qr_code', $barcode)
                    ->orWhere('serial_number', $barcode);
            })->first();

            if (!$asset) {
                $results['not_found'][] = $barcode;
            } elseif ($asset->organization_id !== $organizationId) {
                $results['unauthorized'][] = $barcode;
            } else {
                $results['found'][] = [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'barcode' => $barcode,
                    'status' => $asset->status,
                ];
            }
        }

        return $results;
    }
}
