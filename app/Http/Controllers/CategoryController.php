<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::with(['parent', 'children', 'assetsCount']);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('parent_category_id')) {
            if ($request->input('parent_category_id') === 'null') {
                $query->whereNull('parent_category_id');
            } else {
                $query->where('parent_category_id', $request->input('parent_category_id'));
            }
        }

        // Include asset counts
        $query->withCount('assets');

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        
        // Validate sort field
        $validSortFields = ['name', 'pm_frequency_months', 'useful_life_years', 'assets_count', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'name';
        }
        
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $categories = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $categories->items(),
            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ],
        ]);
    }

    /**
     * Get hierarchical category tree.
     */
    public function tree(Request $request): JsonResponse
    {
        $query = Category::with(['children', 'parent', 'assetsCount'])
            ->withCount('assets');

        // Apply filters
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $categories = $query->orderBy('name')->get();

        // Build tree structure
        $tree = $this->buildCategoryTree($categories);

        return response()->json([
            'success' => true,
            'data' => $tree,
        ]);
    }

    /**
     * Build hierarchical category tree.
     */
    private function buildCategoryTree($categories, $parentId = null): array
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category->parent_category_id === $parentId) {
                $node = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'pm_frequency_months' => $category->pm_frequency_months,
                    'useful_life_years' => $category->useful_life_years,
                    'depreciation_method' => $category->depreciation_method,
                    'is_active' => $category->is_active,
                    'assets_count' => $category->assets_count,
                    'created_at' => $category->created_at->toISOString(),
                    'updated_at' => $category->updated_at->toISOString(),
                ];
                
                $children = $this->buildCategoryTree($categories, $category->id);
                if (!empty($children)) {
                    $node['children'] = $children;
                    $node['has_children'] = true;
                } else {
                    $node['has_children'] = false;
                }
                
                $tree[] = $node;
            }
        }
        
        return $tree;
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'parent_category_id' => 'nullable|uuid|exists:categories,id',
            'pm_frequency_months' => 'required|integer|min:1|max:36',
            'useful_life_years' => 'required|integer|min:1|max:30',
            'depreciation_method' => ['required', Rule::in(['straight_line', 'declining_balance'])],
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = Category::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category->load(['parent', 'children']),
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): JsonResponse
    {
        $category->load(['parent', 'children', 'assets']);

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => 'sometimes|nullable|string|max:1000',
            'parent_category_id' => 'sometimes|nullable|uuid|exists:categories,id',
            'pm_frequency_months' => 'sometimes|required|integer|min:1|max:36',
            'useful_life_years' => 'sometimes|required|integer|min:1|max:30',
            'depreciation_method' => ['sometimes', 'required', Rule::in(['straight_line', 'declining_balance'])],
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category->load(['parent', 'children']),
        ]);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        // Check if category has associated assets or child categories
        if ($category->assets()->exists() || $category->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with associated assets or child categories',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

    /**
     * Get active categories.
     */
    public function active(): JsonResponse
    {
        $categories = Category::active()
            ->with(['parent', 'children'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get root categories (categories without parent).
     */
    public function root(): JsonResponse
    {
        $categories = Category::root()
            ->with(['children', 'assetsCount'])
            ->withCount('assets')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get category statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_categories' => Category::count(),
            'active_categories' => Category::where('is_active', true)->count(),
            'inactive_categories' => Category::where('is_active', false)->count(),
            'root_categories' => Category::whereNull('parent_category_id')->count(),
            'categories_with_assets' => Category::has('assets')->count(),
            'categories_without_assets' => Category::doesntHave('assets')->count(),
            'average_pm_frequency' => Category::avg('pm_frequency_months'),
            'average_useful_life' => Category::avg('useful_life_years'),
            'depreciation_methods' => Category::select('depreciation_method', DB::raw('count(*) as count'))
                ->groupBy('depreciation_method')
                ->get(),
            'top_categories_by_assets' => Category::withCount('assets')
                ->orderBy('assets_count', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'assets_count']),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get category assets.
     */
    public function assets(Category $category, Request $request): JsonResponse
    {
        $query = $category->assets()->with(['location', 'department', 'creator']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $assets = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category->load(['parent', 'children']),
                'assets' => $assets->items(),
                'pagination' => [
                    'current_page' => $assets->currentPage(),
                    'last_page' => $assets->lastPage(),
                    'per_page' => $assets->perPage(),
                    'total' => $assets->total(),
                    'from' => $assets->firstItem(),
                    'to' => $assets->lastItem(),
                ],
            ],
        ]);
    }

    /**
     * Get category maintenance schedule.
     */
    public function maintenanceSchedule(Category $category): JsonResponse
    {
        $assets = $category->assets()->where('status', 'active')->get();
        
        $schedule = [];
        $currentDate = now();
        
        foreach ($assets as $asset) {
            $lastMaintenanceDate = $asset->updated_at; // Placeholder - would use actual maintenance date
            $nextMaintenanceDate = $lastMaintenanceDate->copy()->addMonths($category->pm_frequency_months);
            
            $schedule[] = [
                'asset_id' => $asset->id,
                'asset_name' => $asset->name,
                'serial_number' => $asset->serial_number,
                'last_maintenance_date' => $lastMaintenanceDate->format('Y-m-d'),
                'next_maintenance_date' => $nextMaintenanceDate->format('Y-m-d'),
                'days_until_maintenance' => $currentDate->diffInDays($nextMaintenanceDate, false),
                'is_overdue' => $nextMaintenanceDate->isPast(),
                'priority' => $this->getMaintenancePriority($nextMaintenanceDate, $currentDate),
            ];
        }

        // Sort by maintenance date
        usort($schedule, function ($a, $b) {
            return $a['next_maintenance_date'] <=> $b['next_maintenance_date'];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'maintenance_schedule' => $schedule,
                'summary' => [
                    'total_assets' => count($schedule),
                    'overdue_count' => count(array_filter($schedule, fn($s) => $s['is_overdue'])),
                    'due_this_month' => count(array_filter($schedule, fn($s) => $s['days_until_maintenance'] <= 30 && $s['days_until_maintenance'] >= 0)),
                    'due_next_month' => count(array_filter($schedule, fn($s) => $s['days_until_maintenance'] > 30 && $s['days_until_maintenance'] <= 60)),
                ],
            ],
        ]);
    }

    /**
     * Get maintenance priority.
     */
    private function getMaintenancePriority($nextMaintenanceDate, $currentDate): string
    {
        $daysDiff = $currentDate->diffInDays($nextMaintenanceDate, false);
        
        if ($daysDiff < 0) {
            return 'overdue';
        } elseif ($daysDiff <= 7) {
            return 'urgent';
        } elseif ($daysDiff <= 30) {
            return 'high';
        } elseif ($daysDiff <= 60) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Bulk update categories.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'updates' => 'required|array',
            'updates.pm_frequency_months' => 'sometimes|integer|min:1|max:36',
            'updates.useful_life_years' => 'sometimes|integer|min:1|max:30',
            'updates.depreciation_method' => 'sometimes|in:straight_line,declining_balance',
            'updates.is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $categoryIds = $request->input('category_ids');
        $updates = $request->input('updates');

        $updatedCount = Category::whereIn('id', $categoryIds)->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Categories updated successfully',
            'data' => [
                'updated_count' => $updatedCount,
                'category_ids' => $categoryIds,
                'updates' => $updates,
            ],
        ]);
    }

    /**
     * Duplicate category.
     */
    public function duplicate(Category $category, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'include_children' => 'boolean',
            'include_assets' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newCategory = $category->replicate();
        $newCategory->name = $request->input('name');
        $newCategory->save();

        $duplicatedItems = [
            'category_id' => $newCategory->id,
            'original_category_id' => $category->id,
        ];

        // Duplicate children if requested
        if ($request->boolean('include_children')) {
            $children = $category->children;
            foreach ($children as $child) {
                $newChild = $child->replicate();
                $newChild->parent_category_id = $newCategory->id;
                $newChild->save();
            }
            $duplicatedItems['children_count'] = $children->count();
        }

        // Duplicate assets if requested
        if ($request->boolean('include_assets')) {
            $assets = $category->assets;
            foreach ($assets as $asset) {
                $newAsset = $asset->replicate();
                $newAsset->category_id = $newCategory->id;
                $newAsset->serial_number = $this->generateUniqueSerialNumber($asset->serial_number);
                $newAsset->save();
            }
            $duplicatedItems['assets_count'] = $assets->count();
        }

        return response()->json([
            'success' => true,
            'message' => 'Category duplicated successfully',
            'data' => [
                'new_category' => $newCategory->load(['parent', 'children']),
                'duplicated_items' => $duplicatedItems,
            ],
        ], 201);
    }

    /**
     * Generate unique serial number for duplicated asset.
     */
    private function generateUniqueSerialNumber($originalSerial): string
    {
        $counter = 1;
        do {
            $newSerial = $originalSerial . '-COPY-' . $counter;
            $exists = Asset::where('serial_number', $newSerial)->exists();
            $counter++;
        } while ($exists);

        return $newSerial;
    }

    /**
     * Export categories to CSV.
     */
    public function export(Request $request): JsonResponse
    {
        $query = Category::with(['parent', 'assetsCount'])->withCount('assets');

        // Apply filters
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('parent_category_id')) {
            if ($request->input('parent_category_id') === 'null') {
                $query->whereNull('parent_category_id');
            } else {
                $query->where('parent_category_id', $request->input('parent_category_id'));
            }
        }

        $categories = $query->orderBy('name')->get();

        $exportData = $categories->map(function ($category) {
            return [
                'ID' => $category->id,
                'Name' => $category->name,
                'Description' => $category->description,
                'Parent Category' => $category->parent?->name ?? 'Root',
                'PM Frequency (Months)' => $category->pm_frequency_months,
                'Useful Life (Years)' => $category->useful_life_years,
                'Depreciation Method' => $category->depreciation_method,
                'Is Active' => $category->is_active ? 'Yes' : 'No',
                'Assets Count' => $category->assets_count,
                'Created At' => $category->created_at->format('Y-m-d H:i:s'),
                'Updated At' => $category->updated_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        $filename = 'categories_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->json([
            'success' => true,
            'message' => 'Categories exported successfully',
            'data' => [
                'filename' => $filename,
                'download_url' => "/api/categories/download/{$filename}",
                'record_count' => count($exportData),
                'export_data' => $exportData,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }
}
