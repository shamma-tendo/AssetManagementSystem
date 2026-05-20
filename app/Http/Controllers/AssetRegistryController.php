<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssetRegistryController extends Controller
{
    /**
     * Display the asset registry page.
     */
    public function index(Request $request)
    {
        $q = trim($request->input('q', ''));

        $dbAssets = Asset::with(['category', 'location', 'maintenanceHistories'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('serial_number', 'like', "%{$q}%")
                        ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$q}%"));
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $assets = $dbAssets->map(fn($a) => $a->formatted)->values()->toArray();

        $total       = max(1, Asset::count());
        $operational = Asset::where('status', 'active')->count();
        $inRepair    = Asset::where('status', 'under_maintenance')->count();
        $uptimeAvg   = round($operational / $total * 100, 1);

        $stats = [
            'totalAssets' => $total,
            'operational' => $operational,
            'inRepair'    => $inRepair,
            'uptimeAvg'   => $uptimeAvg,
            'bars'        => [
                'totalAssets' => (int) round($operational / $total * 100),
                'operational' => (int) round($operational / $total * 100),
                'inRepair'    => (int) round($inRepair    / $total * 100),
                'uptimeAvg'   => (int) $uptimeAvg,
            ],
        ];

        $categories         = Category::orderBy('name')->get(['id', 'name']);
        $locations          = Location::orderBy('name')->get(['id', 'name']);
        $maintenanceHistory = $this->getMaintenanceHistory();

        return view('asset-registry', compact('stats', 'assets', 'maintenanceHistory', 'categories', 'locations'));
    }

    /**
     * Get real maintenance history data grouped by month for the last 6 months.
     * Counts completed work orders per type (preventive / corrective / predictive).
     */
    private function getMaintenanceHistory(): array
    {
        $labels = [];
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date     = now()->subMonths($i);
            $labels[] = $date->format('M');
            $months[] = [$date->year, $date->month];
        }

        $types = ['preventive', 'corrective', 'predictive'];

        $rows = DB::table('work_orders')
            ->where('status', 'completed')
            ->whereIn('type', $types)
            ->where('completed_at', '>=', now()->subMonths(6)->startOfMonth())
            ->selectRaw("strftime('%Y', completed_at) as yr, strftime('%m', completed_at) as mo, type, COUNT(*) as cnt")
            ->groupBy('yr', 'mo', 'type')
            ->get();

        $lookup = [];
        foreach ($rows as $row) {
            $lookup[$row->yr . '-' . $row->mo][$row->type] = $row->cnt;
        }

        $result = ['labels' => $labels];
        foreach ($types as $type) {
            $result[$type] = array_map(
                fn($m) => (int) ($lookup[$m[0] . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT)][$type] ?? 0),
                $months
            );
        }

        return $result;
    }
    
    /**
     * Get asset details via API.
     */
    public function getAssetDetails(string $assetId)
    {
        $asset = Asset::with(['category', 'location'])
            ->where('serial_number', $assetId)
            ->first();

        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Asset not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->formatAsset($asset)]);
    }
    
    /**
     * Export assets data.
     */
    public function exportAssets(Request $request)
    {
        $assets = Asset::with(['category', 'location'])
            ->get()
            ->map(fn($a) => $this->formatAsset($a));

        $format = $request->input('format', 'csv');

        return match ($format) {
            'excel' => $this->exportExcel($assets),
            default => $this->exportCsv($assets),
        };
    }
    
    /**
     * Export data as CSV.
     */
    private function exportCsv($assets)
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="asset_registry_' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($assets) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Asset ID', 'Name', 'Category', 'Location', 'Health', 'Status', 'Last Maintenance', 'Manufacturer', 'Installed Date', 'Warranty End']);
            foreach ($assets as $asset) {
                fputcsv($file, [
                    $asset['id'], $asset['name'], $asset['category'], $asset['location'],
                    $asset['health'] . '%', $asset['status'], $asset['lastMaintenance'],
                    $asset['manufacturer'], $asset['installedDate'], $asset['warrantyEnd'],
                ]);
            }
            fclose($file);
        }, 200, $headers);
    }
    
    /**
     * Export data as Excel.
     */
    private function exportExcel($assets)
    {
        return response()->json([
            'message' => 'Excel export not implemented yet',
            'data' => $assets
        ]);
    }

    /**
     * Store a new asset in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'serial_number'   => 'required|string|max:100|unique:assets,serial_number',
            'category_id'     => 'required|exists:categories,id',
            'location_id'     => 'required|exists:locations,id',
            'status'          => 'required|in:ordered,received,active,under_maintenance,retired,disposed',
            'purchase_date'   => 'required|date',
            'purchase_cost'   => 'required|numeric|min:0',
            'manufacturer'    => 'nullable|string|max:255',
            'warranty_expiry' => 'nullable|date',
        ]);

        Asset::create(array_merge($validated, ['created_by' => Auth::id()]));

        return redirect()->route('asset-registry')
            ->with('success', 'Asset "' . $validated['name'] . '" created successfully!');
    }

    /**
     * Update an existing asset.
     */
    public function update(Request $request, string $assetId)
    {
        $asset = Asset::where('serial_number', $assetId)->firstOrFail();

        $validated = $request->validate([
            'name'            => 'sometimes|required|string|max:255',
            'category_id'     => 'sometimes|required|exists:categories,id',
            'location_id'     => 'sometimes|required|exists:locations,id',
            'status'          => 'sometimes|required|in:ordered,received,active,under_maintenance,retired,disposed',
            'purchase_date'   => 'sometimes|required|date',
            'purchase_cost'   => 'sometimes|required|numeric|min:0',
            'manufacturer'    => 'nullable|string|max:255',
            'warranty_expiry' => 'nullable|date',
        ]);

        $asset->update(array_merge($validated, ['updated_by' => Auth::id()]));

        return response()->json([
            'success' => true,
            'message' => 'Asset updated successfully',
            'data' => $this->formatAsset($asset->fresh()),
        ]);
    }

    /**
     * Delete an asset.
     */
    public function destroy(string $assetId)
    {
        $asset = Asset::where('serial_number', $assetId)->firstOrFail();

        // Prevent deletion if asset has active work orders
        if ($asset->workOrders()->whereNotIn('status', ['completed', 'closed', 'cancelled'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete asset with active work orders',
            ], 422);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully',
        ]);
    }
}
