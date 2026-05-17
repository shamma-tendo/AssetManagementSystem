<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDepreciation;
use App\Models\DepreciationMethod;
use App\Models\DepreciationEntry;
use App\Models\User;
use App\Models\UserRole;
use App\Services\DepreciationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepreciationController extends Controller
{
    protected DepreciationService $depreciationService;

    public function __construct(DepreciationService $depreciationService)
    {
        $this->depreciationService = $depreciationService;
    }

    /**
     * Display a listing of asset depreciations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AssetDepreciation::with(['asset', 'depreciationMethod', 'depreciationEntries']);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('asset', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('asset_tag', 'like', "%{$search}%");
            });
        }

        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->input('asset_id'));
        }

        if ($request->has('depreciation_method_id')) {
            $query->where('depreciation_method_id', $request->input('depreciation_method_id'));
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            switch ($status) {
                case 'fully_depreciated':
                    $query->fullyDepreciated();
                    break;
                case 'partially_depreciated':
                    $query->partiallyDepreciated();
                    break;
                case 'not_started':
                    $query->notStarted();
                    break;
                case 'in_progress':
                    $query->partiallyDepreciated();
                    break;
            }
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $depreciations = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $depreciations->items(),
            'pagination' => [
                'current_page' => $depreciations->currentPage(),
                'last_page' => $depreciations->lastPage(),
                'per_page' => $depreciations->perPage(),
                'total' => $depreciations->total(),
                'from' => $depreciations->firstItem(),
                'to' => $depreciations->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created asset depreciation in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|uuid|exists:assets,id',
            'depreciation_method_id' => 'required|uuid|exists:depreciation_methods,id',
            'purchase_cost' => 'required|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_years' => 'required|integer|min:1|max:50',
            'useful_life_hours' => 'nullable|integer|min:1',
            'depreciation_start_date' => 'required|date',
            'depreciation_end_date' => 'nullable|date|after:depreciation_start_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = auth()->id();

        // Check if asset already has depreciation
        if (AssetDepreciation::where('asset_id', $validated['asset_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Asset already has a depreciation schedule',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $depreciation = AssetDepreciation::create($validated);
            
            // Calculate and set depreciation values
            $depreciation->recalculateDepreciation();
            
            // Set initial values
            $depreciation->update([
                'current_book_value' => $depreciation->purchase_cost,
                'accumulated_depreciation' => 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Depreciation schedule created successfully',
                'data' => $depreciation->load(['asset', 'depreciationMethod']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create depreciation schedule',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Display the specified asset depreciation.
     */
    public function show(AssetDepreciation $depreciation): JsonResponse
    {
        $depreciation->load([
            'asset',
            'depreciationMethod',
            'depreciationEntries' => function ($query) {
                $query->orderBy('period_date', 'desc');
            },
            'creator',
            'updater',
        ]);

        return response()->json([
            'success' => true,
            'data' => $depreciation,
        ]);
    }

    /**
     * Update the specified asset depreciation in storage.
     */
    public function update(Request $request, AssetDepreciation $depreciation): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'depreciation_method_id' => 'sometimes|required|uuid|exists:depreciation_methods,id',
            'salvage_value' => 'sometimes|nullable|numeric|min:0',
            'useful_life_years' => 'sometimes|required|integer|min:1|max:50',
            'useful_life_hours' => 'sometimes|nullable|integer|min:1',
            'depreciation_start_date' => 'sometimes|required|date',
            'depreciation_end_date' => 'sometimes|nullable|date|after:depreciation_start_date',
            'notes' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['updated_by'] = auth()->id();

        // Check if depreciation has entries
        if ($depreciation->depreciationEntries()->exists() && 
            isset($validated['depreciation_method_id']) && 
            $validated['depreciation_method_id'] != $depreciation->depreciation_method_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change depreciation method after entries have been created',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $depreciation->update($validated);
            
            // Recalculate depreciation if key parameters changed
            if (isset($validated['useful_life_years']) || 
                isset($validated['salvage_value']) || 
                isset($validated['depreciation_method_id'])) {
                $depreciation->recalculateDepreciation();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Depreciation schedule updated successfully',
                'data' => $depreciation->fresh()->load(['asset', 'depreciationMethod']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update depreciation schedule',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Remove the specified asset depreciation from storage.
     */
    public function destroy(AssetDepreciation $depreciation): JsonResponse
    {
        // Check if depreciation has entries
        if ($depreciation->depreciationEntries()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete depreciation schedule with entries',
            ], 422);
        }

        $depreciation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Depreciation schedule deleted successfully',
        ]);
    }

    /**
     * Get depreciation entries.
     */
    public function entries(Request $request): JsonResponse
    {
        $query = DepreciationEntry::with(['assetDepreciation.asset', 'creator']);

        // Apply filters
        if ($request->has('asset_id')) {
            $query->whereHas('assetDepreciation', function ($q) use ($request) {
                $q->where('asset_id', $request->input('asset_id'));
            });
        }

        if ($request->has('depreciation_id')) {
            $query->where('asset_depreciation_id', $request->input('depreciation_id'));
        }

        if ($request->has('year')) {
            $query->byPeriod($request->input('year'));
        }

        if ($request->has('year') && $request->has('month')) {
            $query->byPeriod($request->input('year'), $request->input('month'));
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->dateRange($request->input('date_from'), $request->input('date_to'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'period_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $entries = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $entries->items(),
            'pagination' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        ]);
    }

    /**
     * Create depreciation entry.
     */
    public function createEntry(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'asset_depreciation_id' => 'required|uuid|exists:asset_depreciations,id',
            'period_date' => 'required|date',
            'depreciation_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = auth()->id();

        DB::beginTransaction();
        try {
            $depreciation = AssetDepreciation::findOrFail($validated['asset_depreciation_id']);
            
            // Check if entry already exists for this period
            if ($depreciation->depreciationEntries()->where('period_date', $validated['period_date'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Depreciation entry already exists for this period',
                ], 422);
            }

            // Create entry
            $entry = $depreciation->createDepreciationEntry(
                Carbon::parse($validated['period_date']),
                $validated['depreciation_amount'],
                $validated['description'] ?? null
            );

            // Update depreciation schedule
            $depreciation->update([
                'accumulated_depreciation' => $depreciation->accumulated_depreciation + $validated['depreciation_amount'],
                'current_book_value' => $depreciation->current_book_value - $validated['depreciation_amount'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Depreciation entry created successfully',
                'data' => $entry->load(['assetDepreciation.asset', 'creator']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create depreciation entry',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Process monthly depreciation for all assets.
     */
    public function processMonthlyDepreciation(): JsonResponse
    {
        $results = $this->depreciationService->processMonthlyDepreciation();

        return response()->json([
            'success' => true,
            'message' => 'Monthly depreciation processing completed',
            'data' => $results,
        ]);
    }

    /**
     * Process monthly depreciation for a specific asset.
     */
    public function processAssetDepreciation(Request $request, Asset $asset): JsonResponse
    {
        $depreciation = $asset->depreciation;
        
        if (!$depreciation) {
            return response()->json([
                'success' => false,
                'message' => 'Asset does not have a depreciation schedule',
            ], 422);
        }

        $entry = $depreciation->processMonthlyDepreciation();

        if (!$entry) {
            return response()->json([
                'success' => true,
                'message' => 'No depreciation to process for this period',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Depreciation processed successfully',
            'data' => $entry->load(['assetDepreciation.asset', 'creator']),
        ]);
    }

    /**
     * Get depreciation methods.
     */
    public function methods(): JsonResponse
    {
        $methods = DepreciationMethod::active()->get();

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Get depreciation statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_assets' => Asset::count(),
            'assets_with_depreciation' => Asset::has('depreciation')->count(),
            'depreciation_schedules' => AssetDepreciation::count(),
            'active_schedules' => AssetDepreciation::active()->count(),
            'by_status' => [
                'not_started' => AssetDepreciation::notStarted()->count(),
                'in_progress' => AssetDepreciation::partiallyDepreciated()->count(),
                'fully_depreciated' => AssetDepreciation::fullyDepreciated()->count(),
                'ended' => AssetDepreciation::whereHas('depreciationEnd', function ($q) {
                    $q->where('depreciation_end_date', '<', now());
                })->count(),
            ],
            'by_method' => AssetDepreciation::with('depreciationMethod')
                ->get()
                ->groupBy('depreciationMethod.name')
                ->mapWithKeys(function ($group, $methodName) {
                    return [$methodName => $group->count()];
                }),
            'financial_summary' => [
                'total_purchase_cost' => AssetDepreciation::sum('purchase_cost'),
                'total_salvage_value' => AssetDepreciation::sum('salvage_value'),
                'total_accumulated_depreciation' => AssetDepreciation::sum('accumulated_depreciation'),
                'total_current_book_value' => AssetDepreciation::sum('current_book_value'),
                'total_annual_depreciation' => AssetDepreciation::sum('annual_depreciation'),
                'total_monthly_depreciation' => AssetDepreciation::sum('monthly_depreciation'),
            ],
            'depreciation_entries' => [
                'total_entries' => DepreciationEntry::count(),
                'this_year' => DepreciationEntry::byPeriod(now()->year)->count(),
                'this_month' => DepreciationEntry::byPeriod(now()->year, now()->month)->count(),
                'total_depreciation_amount' => DepreciationEntry::sum('depreciation_amount'),
                'this_year_amount' => DepreciationEntry::byPeriod(now()->year)->sum('depreciation_amount'),
                'this_month_amount' => DepreciationEntry::byPeriod(now()->year, now()->month)->sum('depreciation_amount'),
            ],
            'age_analysis' => $this->calculateAgeAnalysis(),
            'value_analysis' => $this->calculateValueAnalysis(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get depreciation report.
     */
    public function report(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:summary,detailed,forecast,comparison',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'asset_ids' => 'nullable|array',
            'asset_ids.*' => 'uuid|exists:assets,id',
            'depreciation_method_ids' => 'nullable|array',
            'depreciation_method_ids.*' => 'uuid|exists:depreciation_methods,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $reportType = $validated['report_type'];

        $report = match($reportType) {
            'summary' => $this->generateSummaryReport($validated),
            'detailed' => $this->generateDetailedReport($validated),
            'forecast' => $this->generateForecastReport($validated),
            'comparison' => $this->generateComparisonReport($validated),
        };

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Calculate age analysis.
     */
    private function calculateAgeAnalysis(): array
    {
        $analysis = [
            '0_1_years' => 0,
            '1_3_years' => 0,
            '3_5_years' => 0,
            '5_10_years' => 0,
            '10_plus_years' => 0,
        ];

        AssetDepreciation::with('asset')->get()->each(function ($depreciation) use (&$analysis) {
            $age = $depreciation->years_elapsed;
            
            if ($age <= 1) {
                $analysis['0_1_years']++;
            } elseif ($age <= 3) {
                $analysis['1_3_years']++;
            } elseif ($age <= 5) {
                $analysis['3_5_years']++;
            } elseif ($age <= 10) {
                $analysis['5_10_years']++;
            } else {
                $analysis['10_plus_years']++;
            }
        });

        return $analysis;
    }

    /**
     * Calculate value analysis.
     */
    private function calculateValueAnalysis(): array
    {
        $analysis = [
            'total_value' => 0,
            'fully_depreciated_value' => 0,
            'partially_depreciated_value' => 0,
            'not_depreciated_value' => 0,
        ];

        AssetDepreciation::get()->each(function ($depreciation) use (&$analysis) {
            $analysis['total_value'] += $depreciation->current_book_value;
            
            if ($depreciation->isFullyDepreciated()) {
                $analysis['fully_depreciated_value'] += $depreciation->current_book_value;
            } elseif ($depreciation->accumulated_depreciation > 0) {
                $analysis['partially_depreciated_value'] += $depreciation->current_book_value;
            } else {
                $analysis['not_depreciated_value'] += $depreciation->current_book_value;
            }
        });

        return $analysis;
    }

    /**
     * Generate summary report.
     */
    private function generateSummaryReport(array $filters): array
    {
        $query = AssetDepreciation::with(['asset', 'depreciationMethod']);

        if (isset($filters['asset_ids'])) {
            $query->whereIn('asset_id', $filters['asset_ids']);
        }

        if (isset($filters['depreciation_method_ids'])) {
            $query->whereIn('depreciation_method_id', $filters['depreciation_method_ids']);
        }

        $depreciations = $query->get();

        return [
            'report_type' => 'summary',
            'period' => 'All Time',
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_assets' => $depreciations->count(),
                'total_purchase_cost' => $depreciations->sum('purchase_cost'),
                'total_current_book_value' => $depreciations->sum('current_book_value'),
                'total_accumulated_depreciation' => $depreciations->sum('accumulated_depreciation'),
                'average_depreciation_rate' => $depreciations->avg('depreciation_rate') * 100,
                'total_annual_depreciation' => $depreciations->sum('annual_depreciation'),
            ],
            'by_method' => $depreciations->groupBy('depreciationMethod.name')
                ->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total_value' => $group->sum('current_book_value'),
                        'total_depreciation' => $group->sum('accumulated_depreciation'),
                        'average_rate' => $group->avg('depreciation_rate') * 100,
                    ];
                }),
            'by_status' => [
                'not_started' => $depreciations->where('accumulated_depreciation', 0)->count(),
                'in_progress' => $depreciations->where('accumulated_depreciation', '>', 0)
                    ->where('current_book_value', '>', 'salvage_value')->count(),
                'fully_depreciated' => $depreciations->where('current_book_value', '<=', 'salvage_value')->count(),
            ],
        ];
    }

    /**
     * Generate detailed report.
     */
    private function generateDetailedReport(array $filters): array
    {
        $query = AssetDepreciation::with(['asset', 'depreciationMethod', 'depreciationEntries']);

        if (isset($filters['asset_ids'])) {
            $query->whereIn('asset_id', $filters['asset_ids']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereHas('depreciationEntries', function ($q) use ($filters) {
                $q->whereBetween('period_date', [$filters['date_from'], $filters['date_to']]);
            });
        }

        $depreciations = $query->get();

        return [
            'report_type' => 'detailed',
            'period' => $filters['date_from'] && $filters['date_to'] 
                ? "{$filters['date_from']} to {$filters['date_to']}" 
                : 'All Time',
            'generated_at' => now()->toISOString(),
            'assets' => $depreciations->map(function ($depreciation) {
                return [
                    'asset_name' => $depreciation->asset->name,
                    'asset_tag' => $depreciation->asset->asset_tag,
                    'method' => $depreciation->depreciationMethod->name,
                    'purchase_cost' => $depreciation->purchase_cost,
                    'current_book_value' => $depreciation->current_book_value,
                    'accumulated_depreciation' => $depreciation->accumulated_depreciation,
                    'depreciation_percentage' => $depreciation->depreciation_percentage,
                    'status' => $depreciation->depreciation_status_display,
                    'entries_count' => $depreciation->depreciationEntries->count(),
                    'total_depreciation_amount' => $depreciation->depreciationEntries->sum('depreciation_amount'),
                ];
            }),
        ];
    }

    /**
     * Generate forecast report.
     */
    private function generateForecastReport(array $filters): array
    {
        $query = AssetDepreciation::with(['asset', 'depreciationMethod']);

        if (isset($filters['asset_ids'])) {
            $query->whereIn('asset_id', $filters['asset_ids']);
        }

        $depreciations = $query->where('is_active', true)->get();

        $forecast = [];
        $years = 5; // Forecast for next 5 years

        for ($year = 1; $year <= $years; $year++) {
            $yearData = [
                'year' => now()->year + $year,
                'total_depreciation' => 0,
                'total_book_value' => 0,
                'methods' => [],
            ];

            foreach ($depreciations as $depreciation) {
                $remainingYears = $depreciation->remaining_years;
                
                if ($remainingYears >= $year) {
                    $yearDepreciation = $depreciation->annual_depreciation;
                    $yearBookValue = max($depreciation->salvage_value, 
                        $depreciation->current_book_value - ($yearDepreciation * $year));
                    
                    $yearData['total_depreciation'] += $yearDepreciation;
                    $yearData['total_book_value'] += $yearBookValue;
                    
                    $methodName = $depreciation->depreciationMethod->name;
                    if (!isset($yearData['methods'][$methodName])) {
                        $yearData['methods'][$methodName] = [
                            'depreciation' => 0,
                            'book_value' => 0,
                            'count' => 0,
                        ];
                    }
                    
                    $yearData['methods'][$methodName]['depreciation'] += $yearDepreciation;
                    $yearData['methods'][$methodName]['book_value'] += $yearBookValue;
                    $yearData['methods'][$methodName]['count']++;
                }
            }

            $forecast[] = $yearData;
        }

        return [
            'report_type' => 'forecast',
            'forecast_years' => $years,
            'generated_at' => now()->toISOString(),
            'forecast' => $forecast,
        ];
    }

    /**
     * Generate comparison report.
     */
    private function generateComparisonReport(array $filters): array
    {
        $query = AssetDepreciation::with(['asset', 'depreciationMethod']);

        if (isset($filters['asset_ids'])) {
            $query->whereIn('asset_id', $filters['asset_ids']);
        }

        $depreciations = $query->get();

        $comparison = $depreciations->groupBy('depreciationMethod.name')->map(function ($group, $methodName) {
            return [
                'method_name' => $methodName,
                'asset_count' => $group->count(),
                'total_purchase_cost' => $group->sum('purchase_cost'),
                'total_current_book_value' => $group->sum('current_book_value'),
                'total_accumulated_depreciation' => $group->sum('accumulated_depreciation'),
                'average_depreciation_rate' => $group->avg('depreciation_rate') * 100,
                'average_depreciation_percentage' => $group->avg(function ($item) {
                    return $item->purchase_cost > 0 ? ($item->accumulated_depreciation / $item->purchase_cost) * 100 : 0;
                }),
                'fully_depreciated_count' => $group->where('current_book_value', '<=', 'salvage_value')->count(),
                'average_useful_life' => $group->avg('useful_life_years'),
            ];
        })->values();

        return [
            'report_type' => 'comparison',
            'generated_at' => now()->toISOString(),
            'comparison' => $comparison,
        ];
    }
}
