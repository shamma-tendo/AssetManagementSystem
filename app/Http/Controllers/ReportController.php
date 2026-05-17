<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get dashboard overview data.
     */
    public function dashboard(): JsonResponse
    {
        $user = request()->user();
        
        // Base asset statistics
        $assetStats = [
            'total_assets' => Asset::count(),
            'active_assets' => Asset::where('status', 'active')->count(),
            'under_maintenance' => Asset::where('status', 'under_maintenance')->count(),
            'retired_assets' => Asset::where('status', 'retired')->count(),
            'total_value' => Asset::sum('current_value'),
            'purchase_value' => Asset::sum('purchase_cost'),
        ];

        // Asset status distribution
        $statusDistribution = Asset::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            });

        // Category distribution
        $categoryDistribution = Category::withCount('assets')
            ->whereHas('assets')
            ->orderBy('assets_count', 'desc')
            ->take(10)
            ->get();

        // Location distribution
        $locationDistribution = Location::withCount('assets')
            ->whereHas('assets')
            ->orderBy('assets_count', 'desc')
            ->take(10)
            ->get();

        // Department distribution
        $departmentDistribution = Department::withCount('assets')
            ->whereHas('assets')
            ->orderBy('assets_count', 'desc')
            ->take(10)
            ->get();

        // Recent assets
        $recentAssets = Asset::with(['category', 'location', 'department'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Assets by age (purchase date)
        $ageDistribution = Asset::select(
                DB::raw('CASE 
                    WHEN YEAR(purchase_date) >= YEAR(CURDATE()) - 1 THEN "0-1 years"
                    WHEN YEAR(purchase_date) >= YEAR(CURDATE()) - 3 THEN "1-3 years"
                    WHEN YEAR(purchase_date) >= YEAR(CURDATE()) - 5 THEN "3-5 years"
                    ELSE "5+ years"
                END as age_group'),
                DB::raw('count(*) as count')
            )
            ->groupBy('age_group')
            ->orderBy('age_group')
            ->get();

        // Depreciation summary
        $depreciationSummary = [
            'total_depreciated' => Asset::where('current_value', '<', DB::raw('purchase_cost'))->count(),
            'fully_depreciated' => Asset::where('current_value', '<=', DB::raw('purchase_cost * 0.1'))->count(),
            'average_depreciation_percentage' => Asset::avg(
                DB::raw('((purchase_cost - current_value) / purchase_cost) * 100')
            ),
        ];

        // Maintenance statistics (placeholder for future implementation)
        $maintenanceStats = [
            'scheduled_maintenance' => 0, // Will be implemented with work orders
            'overdue_maintenance' => 0,
            'completed_this_month' => 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'asset_statistics' => $assetStats,
                'status_distribution' => $statusDistribution,
                'category_distribution' => $categoryDistribution,
                'location_distribution' => $locationDistribution,
                'department_distribution' => $departmentDistribution,
                'age_distribution' => $ageDistribution,
                'depreciation_summary' => $depreciationSummary,
                'maintenance_statistics' => $maintenanceStats,
                'recent_assets' => $recentAssets,
                'last_updated' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get asset value report.
     */
    public function assetValueReport(Request $request): JsonResponse
    {
        $query = Asset::query();

        // Apply filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->has('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }
        if ($request->has('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Value by category
        $valueByCategory = $query->clone()
            ->join('categories', 'assets.category_id', '=', 'categories.id')
            ->select('categories.name as category_name')
            ->selectRaw('SUM(assets.purchase_cost) as total_purchase_cost')
            ->selectRaw('SUM(assets.current_value) as total_current_value')
            ->selectRaw('COUNT(assets.id) as asset_count')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_current_value', 'desc')
            ->get();

        // Value by location
        $valueByLocation = $query->clone()
            ->join('locations', 'assets.location_id', '=', 'locations.id')
            ->select('locations.name as location_name')
            ->selectRaw('SUM(assets.purchase_cost) as total_purchase_cost')
            ->selectRaw('SUM(assets.current_value) as total_current_value')
            ->selectRaw('COUNT(assets.id) as asset_count')
            ->groupBy('locations.id', 'locations.name')
            ->orderBy('total_current_value', 'desc')
            ->get();

        // Value by department
        $valueByDepartment = $query->clone()
            ->join('departments', 'assets.department_id', '=', 'departments.id')
            ->select('departments.name as department_name')
            ->selectRaw('SUM(assets.purchase_cost) as total_purchase_cost')
            ->selectRaw('SUM(assets.current_value) as total_current_value')
            ->selectRaw('COUNT(assets.id) as asset_count')
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('total_current_value', 'desc')
            ->get();

        // Depreciation by category
        $depreciationByCategory = $query->clone()
            ->join('categories', 'assets.category_id', '=', 'categories.id')
            ->select('categories.name as category_name')
            ->selectRaw('SUM(assets.purchase_cost - assets.current_value) as total_depreciation')
            ->selectRaw('AVG(((assets.purchase_cost - assets.current_value) / assets.purchase_cost) * 100) as avg_depreciation_percentage')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_depreciation', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'value_by_category' => $valueByCategory,
                'value_by_location' => $valueByLocation,
                'value_by_department' => $valueByDepartment,
                'depreciation_by_category' => $depreciationByCategory,
                'summary' => [
                    'total_purchase_cost' => $query->sum('purchase_cost'),
                    'total_current_value' => $query->sum('current_value'),
                    'total_depreciation' => $query->sum('purchase_cost') - $query->sum('current_value'),
                    'total_assets' => $query->count(),
                ],
            ],
        ]);
    }

    /**
     * Get asset lifecycle report.
     */
    public function assetLifecycleReport(Request $request): JsonResponse
    {
        $query = Asset::query();

        // Apply filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->has('date_from')) {
            $query->where('purchase_date', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->where('purchase_date', '<=', $request->input('date_to'));
        }

        // Assets by purchase year
        $assetsByYear = $query->clone()
            ->select(DB::raw('YEAR(purchase_date) as year'))
            ->selectRaw('COUNT(*) as asset_count')
            ->selectRaw('SUM(purchase_cost) as total_cost')
            ->selectRaw('AVG(purchase_cost) as avg_cost')
            ->groupBy(DB::raw('YEAR(purchase_date)'))
            ->orderBy('year', 'desc')
            ->get();

        // Asset status transitions (placeholder for future implementation)
        $statusTransitions = [
            'ordered_to_received' => Asset::where('status', 'received')->count(),
            'received_to_active' => Asset::where('status', 'active')->count(),
            'active_to_maintenance' => Asset::where('status', 'under_maintenance')->count(),
            'maintenance_to_active' => 0, // Will be implemented with work orders
            'active_to_retired' => Asset::where('status', 'retired')->count(),
        ];

        // Asset age analysis
        $ageAnalysis = $query->clone()
            ->select(
                DB::raw('CASE 
                    WHEN TIMESTAMPDIFF(YEAR, purchase_date, CURDATE()) = 0 THEN "New (0-1 years)"
                    WHEN TIMESTAMPDIFF(YEAR, purchase_date, CURDATE()) <= 3 THEN "Young (1-3 years)"
                    WHEN TIMESTAMPDIFF(YEAR, purchase_date, CURDATE()) <= 5 THEN "Mature (3-5 years)"
                    ELSE "Old (5+ years)"
                END as age_category'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(purchase_cost) as total_cost'),
                DB::raw('AVG(purchase_cost) as avg_cost')
            )
            ->groupBy('age_category')
            ->orderBy('age_category')
            ->get();

        // Warranty expiry analysis
        $warrantyAnalysis = $query->clone()
            ->whereNotNull('warranty_expiry')
            ->select(
                DB::raw('CASE 
                    WHEN warranty_expiry < CURDATE() THEN "Expired"
                    WHEN warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN "Expiring Soon"
                    WHEN warranty_expiry <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) THEN "Expiring This Quarter"
                    ELSE "Valid"
                END as warranty_status'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('warranty_status')
            ->orderBy('warranty_status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'assets_by_purchase_year' => $assetsByYear,
                'status_transitions' => $statusTransitions,
                'age_analysis' => $ageAnalysis,
                'warranty_analysis' => $warrantyAnalysis,
                'summary' => [
                    'average_asset_age' => $query->avg(DB::raw('TIMESTAMPDIFF(YEAR, purchase_date, CURDATE())')),
                    'oldest_asset_date' => $query->min('purchase_date'),
                    'newest_asset_date' => $query->max('purchase_date'),
                ],
            ],
        ]);
    }

    /**
     * Get utilization report.
     */
    public function utilizationReport(Request $request): JsonResponse
    {
        // Asset utilization by category
        $utilizationByCategory = Category::with(['assets' => function ($query) {
                $query->select('category_id', 'status')
                    ->selectRaw('COUNT(*) as total_assets')
                    ->selectRaw('SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_assets')
                    ->groupBy('category_id', 'status');
            }])
            ->get()
            ->map(function ($category) {
                $totalAssets = $category->assets->sum('total_assets');
                $activeAssets = $category->assets->where('status', 'active')->sum('total_assets') ?? 0;
                $utilizationRate = $totalAssets > 0 ? ($activeAssets / $totalAssets) * 100 : 0;

                return [
                    'category_name' => $category->name,
                    'total_assets' => $totalAssets,
                    'active_assets' => $activeAssets,
                    'under_maintenance' => $category->assets->where('status', 'under_maintenance')->sum('total_assets') ?? 0,
                    'utilization_rate' => round($utilizationRate, 2),
                ];
            });

        // Location utilization
        $locationUtilization = Location::withCount(['assets', 'assets AS active_assets' => function ($query) {
                $query->where('status', 'active');
            }])
            ->whereHas('assets')
            ->get()
            ->map(function ($location) {
                $utilizationRate = $location->assets_count > 0 ? 
                    ($location->active_assets / $location->assets_count) * 100 : 0;

                return [
                    'location_name' => $location->name,
                    'total_assets' => $location->assets_count,
                    'active_assets' => $location->active_assets,
                    'utilization_rate' => round($utilizationRate, 2),
                ];
            });

        // Department utilization
        $departmentUtilization = Department::withCount(['assets', 'assets AS active_assets' => function ($query) {
                $query->where('status', 'active');
            }])
            ->whereHas('assets')
            ->get()
            ->map(function ($department) {
                $utilizationRate = $department->assets_count > 0 ? 
                    ($department->active_assets / $department->assets_count) * 100 : 0;

                return [
                    'department_name' => $department->name,
                    'total_assets' => $department->assets_count,
                    'active_assets' => $department->active_assets,
                    'utilization_rate' => round($utilizationRate, 2),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'utilization_by_category' => $utilizationByCategory,
                'location_utilization' => $locationUtilization,
                'department_utilization' => $departmentUtilization,
                'summary' => [
                    'overall_utilization_rate' => Asset::where('status', 'active')->count() / Asset::count() * 100,
                    'total_assets' => Asset::count(),
                    'active_assets' => Asset::where('status', 'active')->count(),
                    'under_maintenance' => Asset::where('status', 'under_maintenance')->count(),
                ],
            ],
        ]);
    }

    /**
     * Export report to CSV/Excel.
     */
    public function exportReport(Request $request): JsonResponse
    {
        $reportType = $request->input('report_type', 'asset_value');
        $format = $request->input('format', 'csv');

        // Validate report type
        $validReportTypes = ['asset_value', 'lifecycle', 'utilization', 'asset_list'];
        if (!in_array($reportType, $validReportTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid report type',
            ], 422);
        }

        // Validate format
        $validFormats = ['csv', 'excel'];
        if (!in_array($format, $validFormats)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid format',
            ], 422);
        }

        // Generate report data based on type
        $data = match($reportType) {
            'asset_value' => $this->generateAssetValueData($request),
            'lifecycle' => $this->generateLifecycleData($request),
            'utilization' => $this->generateUtilizationData($request),
            'asset_list' => $this->generateAssetListData($request),
        };

        // Generate file (placeholder - in real implementation, use Laravel Excel)
        $filename = "{$reportType}_report_" . now()->format('Y-m-d_H-i-s') . ".{$format}";

        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'data' => [
                'filename' => $filename,
                'download_url' => "/api/reports/download/{$filename}",
                'record_count' => count($data),
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get custom report builder data.
     */
    public function customReportBuilder(): JsonResponse
    {
        $availableFields = [
            'asset' => [
                'id' => 'Asset ID',
                'name' => 'Asset Name',
                'serial_number' => 'Serial Number',
                'status' => 'Status',
                'purchase_date' => 'Purchase Date',
                'purchase_cost' => 'Purchase Cost',
                'current_value' => 'Current Value',
                'manufacturer' => 'Manufacturer',
                'model' => 'Model',
            ],
            'category' => [
                'name' => 'Category Name',
                'pm_frequency_months' => 'PM Frequency',
                'useful_life_years' => 'Useful Life',
            ],
            'location' => [
                'name' => 'Location Name',
                'city' => 'City',
                'state' => 'State',
            ],
            'department' => [
                'name' => 'Department Name',
                'budget_code' => 'Budget Code',
            ],
        ];

        $availableFilters = [
            'status' => ['ordered', 'received', 'active', 'under_maintenance', 'retired', 'disposed'],
            'category_id' => Category::pluck('name', 'id')->toArray(),
            'location_id' => Location::pluck('name', 'id')->toArray(),
            'department_id' => Department::pluck('name', 'id')->toArray(),
            'date_range' => ['purchase_date', 'warranty_expiry'],
        ];

        $availableAggregations = [
            'count' => 'Count',
            'sum' => 'Sum',
            'avg' => 'Average',
            'min' => 'Minimum',
            'max' => 'Maximum',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'available_fields' => $availableFields,
                'available_filters' => $availableFilters,
                'available_aggregations' => $availableAggregations,
            ],
        ]);
    }

    /**
     * Generate custom report.
     */
    public function generateCustomReport(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'fields' => 'required|array|min:1',
            'filters' => 'array',
            'group_by' => 'string',
            'aggregations' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $fields = $request->input('fields');
        $filters = $request->input('filters', []);
        $groupBy = $request->input('group_by');
        $aggregations = $request->input('aggregations', []);

        // Build query based on requested fields
        $query = Asset::query();

        // Add joins based on fields
        if (in_array('category.name', $fields)) {
            $query->join('categories', 'assets.category_id', '=', 'categories.id');
        }
        if (in_array('location.name', $fields)) {
            $query->join('locations', 'assets.location_id', '=', 'locations.id');
        }
        if (in_array('department.name', $fields)) {
            $query->join('departments', 'assets.department_id', '=', 'departments.id');
        }

        // Apply filters
        foreach ($filters as $field => $value) {
            if ($field === 'status' && is_array($value)) {
                $query->whereIn('assets.status', $value);
            } elseif ($field === 'date_range') {
                // Handle date range filters
                if (isset($value['start'])) {
                    $query->where('assets.' . $value['field'], '>=', $value['start']);
                }
                if (isset($value['end'])) {
                    $query->where('assets.' . $value['field'], '<=', $value['end']);
                }
            } else {
                $query->where($field, $value);
            }
        }

        // Select fields
        $selectFields = [];
        foreach ($fields as $field) {
            if (str_contains($field, '.')) {
                [$table, $column] = explode('.', $field);
                $selectFields[] = "{$table}.{$column} as {$table}_{$column}";
            } else {
                $selectFields[] = "assets.{$field}";
            }
        }

        // Add aggregations
        foreach ($aggregations as $field => $aggregation) {
            $selectFields[] = "{$aggregation}({$field}) as {$aggregation}_{$field}";
        }

        $query->select($selectFields);

        // Group by
        if ($groupBy) {
            $query->groupBy($groupBy);
        }

        $results = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'total_count' => $results->count(),
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    // Helper methods for data generation
    private function generateAssetValueData(Request $request): array
    {
        return Asset::with(['category', 'location', 'department'])
            ->get()
            ->map(function ($asset) {
                return [
                    'Asset Name' => $asset->name,
                    'Serial Number' => $asset->serial_number,
                    'Category' => $asset->category->name ?? 'N/A',
                    'Location' => $asset->location->name ?? 'N/A',
                    'Department' => $asset->department->name ?? 'N/A',
                    'Purchase Cost' => $asset->purchase_cost,
                    'Current Value' => $asset->current_value,
                    'Depreciation' => $asset->purchase_cost - $asset->current_value,
                    'Status' => $asset->status,
                ];
            })
            ->toArray();
    }

    private function generateLifecycleData(Request $request): array
    {
        return Asset::with(['category', 'location', 'department'])
            ->get()
            ->map(function ($asset) {
                return [
                    'Asset Name' => $asset->name,
                    'Serial Number' => $asset->serial_number,
                    'Category' => $asset->category->name ?? 'N/A',
                    'Purchase Date' => $asset->purchase_date->format('Y-m-d'),
                    'Status' => $asset->status,
                    'Current Value' => $asset->current_value,
                    'Age (Years)' => $asset->purchase_date->diffInYears(now()),
                    'Warranty Expiry' => $asset->warranty_expiry?->format('Y-m-d') ?? 'N/A',
                ];
            })
            ->toArray();
    }

    private function generateUtilizationData(Request $request): array
    {
        return Asset::with(['category', 'location', 'department'])
            ->get()
            ->map(function ($asset) {
                return [
                    'Asset Name' => $asset->name,
                    'Serial Number' => $asset->serial_number,
                    'Category' => $asset->category->name ?? 'N/A',
                    'Location' => $asset->location->name ?? 'N/A',
                    'Department' => $asset->department->name ?? 'N/A',
                    'Status' => $asset->status,
                    'Utilization' => $asset->status === 'active' ? 'In Use' : 'Not in Use',
                ];
            })
            ->toArray();
    }

    private function generateAssetListData(Request $request): array
    {
        return Asset::with(['category', 'location', 'department', 'creator'])
            ->get()
            ->map(function ($asset) {
                return [
                    'ID' => $asset->id,
                    'Name' => $asset->name,
                    'Serial Number' => $asset->serial_number,
                    'Category' => $asset->category->name ?? 'N/A',
                    'Location' => $asset->location->name ?? 'N/A',
                    'Department' => $asset->department->name ?? 'N/A',
                    'Status' => $asset->status,
                    'Purchase Date' => $asset->purchase_date->format('Y-m-d'),
                    'Purchase Cost' => $asset->purchase_cost,
                    'Current Value' => $asset->current_value,
                    'Manufacturer' => $asset->manufacturer,
                    'Model' => $asset->model,
                    'Created By' => $asset->creator->full_name ?? 'N/A',
                    'Created At' => $asset->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }
}
