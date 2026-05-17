<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Display a listing of the assets.
     */
    public function index(Request $request): JsonResponse
    {
        // Use the enhanced search service
        $result = $this->searchService->searchAssets($request->all());

        // Save search query for analytics
        if (!empty($request->input('search'))) {
            $this->searchService->saveSearchQuery($request->all(), auth()->id());
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination'],
            'filters_applied' => $result['filters_applied'],
            'search_time' => $result['search_time'],
        ]);
    }

    /**
     * Advanced search endpoint.
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|array',
            'status.*' => 'in:ordered,received,active,under_maintenance,retired,disposed',
            'category_id' => 'nullable|array',
            'category_id.*' => 'exists:categories,id',
            'location_id' => 'nullable|array',
            'location_id.*' => 'exists:locations,id',
            'department_id' => 'nullable|array',
            'department_id.*' => 'exists:departments,id',
            'purchase_date_from' => 'nullable|date',
            'purchase_date_to' => 'nullable|date|after_or_equal:purchase_date_from',
            'purchase_cost_min' => 'nullable|numeric|min:0',
            'purchase_cost_max' => 'nullable|numeric|min:0|gte:purchase_cost_min',
            'current_value_min' => 'nullable|numeric|min:0',
            'current_value_max' => 'nullable|numeric|min:0|gte:current_value_min',
            'warranty_status' => 'nullable|in:expired,expiring_soon,valid',
            'age_years' => 'nullable|integer|min:0|max:50',
            'created_by' => 'nullable|exists:users,id',
            'sort_by' => 'nullable|string|in:name,serial_number,status,purchase_date,purchase_cost,current_value,manufacturer,model,created_at,updated_at',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->searchService->searchAssets($request->all());

        // Save search query for analytics
        if (!empty($request->input('search'))) {
            $this->searchService->saveSearchQuery($request->all(), auth()->id());
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination'],
            'filters_applied' => $result['filters_applied'],
            'search_time' => $result['search_time'],
        ]);
    }

    /**
     * Get search suggestions.
     */
    public function searchSuggestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:50',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $limit = $request->input('limit', 10);
        $suggestions = $this->searchService->getSearchSuggestions($request->input('query'), $limit);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * Get popular search terms.
     */
    public function popularSearches(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $popularTerms = $this->searchService->getPopularSearchTerms($limit);

        return response()->json([
            'success' => true,
            'data' => $popularTerms,
        ]);
    }

    /**
     * Get search filters metadata.
     */
    public function searchFilters(): JsonResponse
    {
        $filters = $this->searchService->getSearchFiltersMetadata();
        $sortOptions = $this->searchService->getSortOptions();

        return response()->json([
            'success' => true,
            'data' => [
                'filters' => $filters,
                'sort_options' => $sortOptions,
            ],
        ]);
    }

    /**
     * Store a newly created asset in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:100|unique:assets,serial_number',
            'category_id' => 'required|uuid|exists:categories,id',
            'location_id' => 'nullable|uuid|exists:locations,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'purchase_date' => 'required|date|before_or_equal:today',
            'purchase_cost' => 'required|numeric|min:0|max:999999999.99',
            'salvage_value' => 'nullable|numeric|min:0|max:purchase_cost',
            'useful_life_years' => 'required|integer|min:1|max:30',
            'depreciation_method' => ['required', Rule::in(['straight_line', 'declining_balance'])],
            'description' => 'nullable|string|max:1000',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'warranty_expiry' => 'nullable|date|after:purchase_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['status'] = 'ordered';
        $validated['current_value'] = $validated['purchase_cost'];
        $validated['created_by'] = auth()->id();

        $asset = Asset::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Asset created successfully',
            'data' => $asset->load(['category', 'location', 'department', 'creator']),
        ], 201);
    }

    /**
     * Display the specified asset.
     */
    public function show(Asset $asset): JsonResponse
    {
        $asset->load(['category', 'location', 'department', 'creator', 'updater', 'workOrders', 'inspections']);

        return response()->json([
            'success' => true,
            'data' => $asset,
        ]);
    }

    /**
     * Update the specified asset in storage.
     */
    public function update(Request $request, Asset $asset): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'serial_number' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('assets', 'serial_number')->ignore($asset->id),
            ],
            'category_id' => 'sometimes|required|uuid|exists:categories,id',
            'location_id' => 'sometimes|nullable|uuid|exists:locations,id',
            'department_id' => 'sometimes|nullable|uuid|exists:departments,id',
            'purchase_date' => 'sometimes|required|date|before_or_equal:today',
            'purchase_cost' => 'sometimes|required|numeric|min:0|max:999999999.99',
            'salvage_value' => 'sometimes|nullable|numeric|min:0|max:purchase_cost',
            'useful_life_years' => 'sometimes|required|integer|min:1|max:30',
            'depreciation_method' => ['sometimes', 'required', Rule::in(['straight_line', 'declining_balance'])],
            'description' => 'sometimes|nullable|string|max:1000',
            'manufacturer' => 'sometimes|nullable|string|max:255',
            'model' => 'sometimes|nullable|string|max:255',
            'warranty_expiry' => 'sometimes|nullable|date|after:purchase_date',
            'status' => ['sometimes', 'required', Rule::in(['ordered', 'received', 'active', 'under_maintenance', 'retired', 'disposed'])],
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

        // Recalculate current value if purchase cost or depreciation method changed
        if (isset($validated['purchase_cost']) || isset($validated['depreciation_method'])) {
            $asset->refresh();
            $validated['current_value'] = $asset->calculateDepreciation();
        }

        $asset->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Asset updated successfully',
            'data' => $asset->load(['category', 'location', 'department', 'creator', 'updater']),
        ]);
    }

    /**
     * Remove the specified asset from storage.
     */
    public function destroy(Asset $asset): JsonResponse
    {
        // Check if asset has associated work orders or inspections
        if ($asset->workOrders()->exists() || $asset->inspections()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete asset with associated work orders or inspections',
            ], 422);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully',
        ]);
    }

    /**
     * Get asset statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_assets' => Asset::count(),
            'active_assets' => Asset::where('status', 'active')->count(),
            'under_maintenance' => Asset::where('status', 'under_maintenance')->count(),
            'retired_assets' => Asset::where('status', 'retired')->count(),
            'total_value' => Asset::sum('current_value'),
            'categories' => Category::withCount('assets')->get(),
            'locations' => Location::withCount('assets')->get(),
            'departments' => Department::withCount('assets')->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get assets by status.
     */
    public function byStatus(string $status): JsonResponse
    {
        if (!in_array($status, ['ordered', 'received', 'active', 'under_maintenance', 'retired', 'disposed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status',
            ], 422);
        }

        $assets = Asset::where('status', $status)
            ->with(['category', 'location', 'department'])
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $assets->items(),
            'pagination' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
        ]);
    }
}
