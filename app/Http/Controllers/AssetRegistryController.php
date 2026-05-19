<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
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

        $dbAssets = Asset::with(['category', 'location'])
            ->withMax('maintenanceHistories', 'performed_date')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('serial_number', 'like', "%{$q}%")
                        ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$q}%"));
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $assets = $dbAssets->map(fn($a) => $this->formatAsset($a))->values()->toArray();

        $stats = [
            'totalAssets' => Asset::count(),
            'operational' => Asset::where('status', 'active')->count(),
            'inRepair'    => Asset::where('status', 'under_maintenance')->count(),
            'uptimeAvg'   => 99.2,
        ];

        $categories         = Category::orderBy('name')->get(['id', 'name']);
        $locations          = Location::orderBy('name')->get(['id', 'name']);
        $maintenanceHistory = $this->getMaintenanceHistory();

        return view('asset-registry', compact('stats', 'assets', 'maintenanceHistory', 'categories', 'locations'));
    }

    /**
     * Map a DB Asset to the array format expected by the view.
     */
    private function formatAsset(Asset $asset): array
    {
        $statusMap = [
            'active'            => 'ACTIVE',
            'ordered'           => 'ORDERED',
            'received'          => 'RECEIVED',
            'under_maintenance' => 'IN REPAIR',
            'retired'           => 'RETIRED',
            'disposed'          => 'DISPOSED',
        ];

        $sv = $asset->status instanceof \BackedEnum
            ? $asset->status->value
            : (string) $asset->status;

        $lastMaintDate = $asset->maintenance_histories_max_performed_date;

        return [
            'id'               => $asset->serial_number,
            'name'             => $asset->name,
            'category'         => $asset->category?->name ?? 'Uncategorized',
            'location'         => $asset->location?->name ?? 'N/A',
            'health'           => $this->calculateHealth($asset, $sv, $lastMaintDate),
            'status'           => $statusMap[$sv] ?? strtoupper($sv),
            'lastMaintenance'  => $lastMaintDate ?? $asset->purchase_date?->format('Y-m-d') ?? 'N/A',
            'manufacturer'     => $asset->manufacturer ?? 'N/A',
            'installedDate'    => $asset->purchase_date?->format('Y-m-d') ?? 'N/A',
            'warrantyEnd'      => $asset->warranty_expiry?->format('Y-m-d') ?? 'N/A',
            'powerRequirement' => 'N/A',
        ];
    }

    /**
     * Calculate a real health score (0-100) from three weighted factors:
     *   40% — age vs. useful life (depreciation-based)
     *   40% — maintenance recency (days since last completed maintenance)
     *   20% — current operational status
     */
    private function calculateHealth(Asset $asset, string $sv, ?string $lastMaintDate): int
    {
        // --- Status factor (20%) ---
        $statusScore = match($sv) {
            'ordered', 'received'  => 100,
            'active'               => 100,
            'under_maintenance'    => 60,
            'retired'              => 20,
            'disposed'             => 0,
            default                => 80,
        };

        // Short-circuit: retired / disposed assets don't need a detailed score
        if (in_array($sv, ['retired', 'disposed'])) {
            return $statusScore;
        }

        // New assets (ordered/received) are considered fully healthy
        if (in_array($sv, ['ordered', 'received'])) {
            return 100;
        }

        // --- Age factor (40%) ---
        $usefulLife = max(1, $asset->useful_life_years ?? 10);
        $ageYears   = $asset->purchase_date
            ? $asset->purchase_date->diffInDays(now()) / 365.25
            : $usefulLife * 0.5; // assume mid-life if unknown
        $ageScore = (int) round(max(0, (1 - min(1.0, $ageYears / $usefulLife)) * 100));

        // --- Maintenance recency factor (40%) ---
        if ($lastMaintDate) {
            $daysSince = now()->diffInDays(Carbon::parse($lastMaintDate));
        } else {
            // No recorded maintenance — penalise based on asset age
            $daysSince = $asset->purchase_date
                ? $asset->purchase_date->diffInDays(now())
                : 730;
        }

        $maintenanceScore = match(true) {
            $daysSince <= 30  => 100,
            $daysSince <= 90  => 85,
            $daysSince <= 180 => 70,
            $daysSince <= 365 => 50,
            $daysSince <= 730 => 30,
            default           => 10,
        };

        // --- Weighted total ---
        $health = ($ageScore * 0.4) + ($maintenanceScore * 0.4) + ($statusScore * 0.2);

        return (int) max(0, min(100, round($health)));
    }
    
    
    /**
     * Get maintenance history data for charts.
     */
    private function getMaintenanceHistory()
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'preventive' => [45, 52, 48, 58, 62, 55],
            'corrective' => [12, 15, 18, 14, 20, 16],
            'predictive' => [8, 10, 12, 15, 18, 22]
        ];
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
}
