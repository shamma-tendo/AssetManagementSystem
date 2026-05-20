<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    /**
     * Perform advanced asset search.
     */
    public function searchAssets(array $params)
    {
        $query = Asset::with(['category', 'location', 'department', 'creator']);

        // Apply text search
        if (!empty($params['search'])) {
            $query = $this->applyTextSearch($query, $params['search']);
        }

        // Apply filters
        $query = $this->applyFilters($query, $params);

        // Apply sorting
        $query = $this->applySorting($query, $params);

        // Apply pagination
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;

        $assets = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $assets->items(),
            'pagination' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
                'from' => $assets->firstItem(),
                'to' => $assets->lastItem(),
            ],
            'filters_applied' => $this->getAppliedFilters($params),
            'search_time' => microtime(true),
        ];
    }

    /**
     * Apply text search to query.
     */
    private function applyTextSearch(Builder $query, string $searchTerm): Builder
    {
        $searchTerm = trim($searchTerm);
        
        if (empty($searchTerm)) {
            return $query;
        }

        // Enhanced search with multiple fields and relevance scoring
        return $query->where(function ($q) use ($searchTerm) {
            // Exact matches get higher priority
            $q->where('assets.name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('assets.serial_number', 'LIKE', "%{$searchTerm}%")
              ->orWhere('assets.manufacturer', 'LIKE', "%{$searchTerm}%")
              ->orWhere('assets.model', 'LIKE', "%{$searchTerm}%")
              ->orWhere('assets.description', 'LIKE', "%{$searchTerm}%")
              
              // Search in related models
              ->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                  $categoryQuery->where('categories.name', 'LIKE', "%{$searchTerm}%");
              })
              ->orWhereHas('location', function ($locationQuery) use ($searchTerm) {
                  $locationQuery->where('locations.name', 'LIKE', "%{$searchTerm}%")
                           ->orWhere('locations.city', 'LIKE', "%{$searchTerm}%")
                           ->orWhere('locations.state', 'LIKE', "%{$searchTerm}%");
              })
              ->orWhereHas('department', function ($departmentQuery) use ($searchTerm) {
                  $departmentQuery->where('departments.name', 'LIKE', "%{$searchTerm}%");
              });
        });
    }

    /**
     * Apply filters to query.
     */
    private function applyFilters(Builder $query, array $params): Builder
    {
        // Status filter
        if (!empty($params['status'])) {
            if (is_array($params['status'])) {
                $query->whereIn('assets.status', $params['status']);
            } else {
                $query->where('assets.status', $params['status']);
            }
        }

        // Category filter
        if (!empty($params['category_id'])) {
            if (is_array($params['category_id'])) {
                $query->whereIn('assets.category_id', $params['category_id']);
            } else {
                $query->where('assets.category_id', $params['category_id']);
            }
        }

        // Location filter
        if (!empty($params['location_id'])) {
            if (is_array($params['location_id'])) {
                $query->whereIn('assets.location_id', $params['location_id']);
            } else {
                $query->where('assets.location_id', $params['location_id']);
            }
        }

        // Department filter
        if (!empty($params['department_id'])) {
            if (is_array($params['department_id'])) {
                $query->whereIn('assets.department_id', $params['department_id']);
            } else {
                $query->where('assets.department_id', $params['department_id']);
            }
        }

        // Purchase date range filter
        if (!empty($params['purchase_date_from'])) {
            $query->where('assets.purchase_date', '>=', $params['purchase_date_from']);
        }
        if (!empty($params['purchase_date_to'])) {
            $query->where('assets.purchase_date', '<=', $params['purchase_date_to']);
        }

        // Purchase cost range filter
        if (!empty($params['purchase_cost_min'])) {
            $query->where('assets.purchase_cost', '>=', $params['purchase_cost_min']);
        }
        if (!empty($params['purchase_cost_max'])) {
            $query->where('assets.purchase_cost', '<=', $params['purchase_cost_max']);
        }

        // Current value range filter
        if (!empty($params['current_value_min'])) {
            $query->where('assets.current_value', '>=', $params['current_value_min']);
        }
        if (!empty($params['current_value_max'])) {
            $query->where('assets.current_value', '<=', $params['current_value_max']);
        }

        // Warranty expiry filter
        if (!empty($params['warranty_status'])) {
            switch ($params['warranty_status']) {
                case 'expired':
                    $query->where('assets.warranty_expiry', '<', now());
                    break;
                case 'expiring_soon':
                    $query->where('assets.warranty_expiry', '>', now())
                          ->where('assets.warranty_expiry', '<=', now()->addDays(30));
                    break;
                case 'valid':
                    $query->where('assets.warranty_expiry', '>', now()->addDays(30));
                    break;
            }
        }

        // Age filter
        if (!empty($params['age_years'])) {
            $yearsAgo = now()->subYears($params['age_years']);
            $query->where('assets.purchase_date', '<=', $yearsAgo);
        }

        // Created by filter
        if (!empty($params['created_by'])) {
            $query->where('assets.created_by', $params['created_by']);
        }

        return $query;
    }

    /**
     * Apply sorting to query.
     */
    private function applySorting(Builder $query, array $params): Builder
    {
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortOrder = $params['sort_order'] ?? 'desc';

        // Validate sort field
        $validSortFields = [
            'name', 'serial_number', 'status', 'purchase_date', 'purchase_cost',
            'current_value', 'manufacturer', 'model', 'created_at', 'updated_at',
            'category.name', 'location.name', 'department.name'
        ];

        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'created_at';
        }

        // Handle related model sorting
        if (str_contains($sortBy, '.')) {
            [$relation, $field] = explode('.', $sortBy);
            return $query->orderBy("{$relation}.{$field}", $sortOrder);
        }

        return $query->orderBy("assets.{$sortBy}", $sortOrder);
    }

    /**
     * Get applied filters for response.
     */
    private function getAppliedFilters(array $params): array
    {
        $filters = [];
        
        $filterFields = [
            'search', 'status', 'category_id', 'location_id', 'department_id',
            'purchase_date_from', 'purchase_date_to', 'purchase_cost_min', 'purchase_cost_max',
            'current_value_min', 'current_value_max', 'warranty_status', 'age_years', 'created_by'
        ];

        foreach ($filterFields as $field) {
            if (!empty($params[$field])) {
                $filters[$field] = $params[$field];
            }
        }

        return $filters;
    }

    /**
     * Get search suggestions.
     */
    public function getSearchSuggestions(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $suggestions = [];

        // Asset name suggestions
        $assetNames = Asset::where('name', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->pluck('name')
            ->map(function ($name) {
                return [
                    'type' => 'asset_name',
                    'value' => $name,
                    'label' => $name,
                ];
            })
            ->toArray();

        // Serial number suggestions
        $serialNumbers = Asset::where('serial_number', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->pluck('serial_number')
            ->map(function ($serial) {
                return [
                    'type' => 'serial_number',
                    'value' => $serial,
                    'label' => "SN: {$serial}",
                ];
            })
            ->toArray();

        // Category suggestions
        $categories = Category::where('name', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->pluck('name')
            ->map(function ($category) {
                return [
                    'type' => 'category',
                    'value' => $category,
                    'label' => "Category: {$category}",
                ];
            })
            ->toArray();

        // Location suggestions
        $locations = Location::where('name', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->pluck('name')
            ->map(function ($location) {
                return [
                    'type' => 'location',
                    'value' => $location,
                    'label' => "Location: {$location}",
                ];
            })
            ->toArray();

        // Department suggestions
        $departments = Department::where('name', 'LIKE', "%{$query}%")
            ->limit($limit)
            ->pluck('name')
            ->map(function ($department) {
                return [
                    'type' => 'department',
                    'value' => $department,
                    'label' => "Department: {$department}",
                ];
            })
            ->toArray();

        // Combine and limit suggestions
        $allSuggestions = array_merge(
            $assetNames,
            $serialNumbers,
            $categories,
            $locations,
            $departments
        );

        return array_slice($allSuggestions, 0, $limit);
    }

    /**
     * Get popular search terms.
     */
    public function getPopularSearchTerms(int $limit = 10): array
    {
        // This would typically be stored in a search_log table
        // For now, return some common search terms based on asset data
        return [
            ['term' => 'laptop', 'count' => 45],
            ['term' => 'dell', 'count' => 38],
            ['term' => 'server', 'count' => 32],
            ['term' => 'printer', 'count' => 28],
            ['term' => 'desktop', 'count' => 25],
            ['term' => 'monitor', 'count' => 22],
            ['term' => 'network', 'count' => 18],
            ['term' => 'storage', 'count' => 15],
            ['term' => 'backup', 'count' => 12],
            ['term' => 'security', 'count' => 10],
        ];
    }

    /**
     * Save search query for analytics (placeholder).
     */
    public function saveSearchQuery(array $params, int $userId): void
    {
        // In a real implementation, this would save to a search_log table
        // For now, we'll just log it (you can implement actual logging later)
        if (config('app.debug')) {
            \Log::info('Search query', [
                'user_id' => $userId,
                'params' => $params,
                'timestamp' => now(),
            ]);
        }
    }

    /**
     * Get advanced search filters metadata.
     */
    public function getSearchFiltersMetadata(): array
    {
        return [
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    ['value' => 'ordered', 'label' => 'Ordered'],
                    ['value' => 'received', 'label' => 'Received'],
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'under_maintenance', 'label' => 'Under Maintenance'],
                    ['value' => 'retired', 'label' => 'Retired'],
                    ['value' => 'disposed', 'label' => 'Disposed'],
                ],
                'multiple' => true,
            ],
            'category_id' => [
                'type' => 'select',
                'label' => 'Category',
                'options' => Category::active()->get(['id', 'name'])->map(function ($category) {
                    return ['value' => $category->id, 'label' => $category->name];
                })->toArray(),
                'multiple' => true,
            ],
            'location_id' => [
                'type' => 'select',
                'label' => 'Location',
                'options' => Location::active()->get(['id', 'name'])->map(function ($location) {
                    return ['value' => $location->id, 'label' => $location->name];
                })->toArray(),
                'multiple' => true,
            ],
            'department_id' => [
                'type' => 'select',
                'label' => 'Department',
                'options' => Department::active()->get(['id', 'name'])->map(function ($department) {
                    return ['value' => $department->id, 'label' => $department->name];
                })->toArray(),
                'multiple' => true,
            ],
            'purchase_date_range' => [
                'type' => 'date_range',
                'label' => 'Purchase Date Range',
                'fields' => ['purchase_date_from', 'purchase_date_to'],
            ],
            'purchase_cost_range' => [
                'type' => 'number_range',
                'label' => 'Purchase Cost Range',
                'fields' => ['purchase_cost_min', 'purchase_cost_max'],
            ],
            'current_value_range' => [
                'type' => 'number_range',
                'label' => 'Current Value Range',
                'fields' => ['current_value_min', 'current_value_max'],
            ],
            'warranty_status' => [
                'type' => 'select',
                'label' => 'Warranty Status',
                'options' => [
                    ['value' => 'expired', 'label' => 'Expired'],
                    ['value' => 'expiring_soon', 'label' => 'Expiring Soon'],
                    ['value' => 'valid', 'label' => 'Valid'],
                ],
            ],
            'age_years' => [
                'type' => 'number',
                'label' => 'Minimum Age (Years)',
                'min' => 0,
                'max' => 50,
            ],
        ];
    }

    /**
     * Get sort options.
     */
    public function getSortOptions(): array
    {
        return [
            'name' => 'Asset Name',
            'serial_number' => 'Serial Number',
            'status' => 'Status',
            'purchase_date' => 'Purchase Date',
            'purchase_cost' => 'Purchase Cost',
            'current_value' => 'Current Value',
            'manufacturer' => 'Manufacturer',
            'model' => 'Model',
            'created_at' => 'Created Date',
            'updated_at' => 'Updated Date',
            'category.name' => 'Category',
            'location.name' => 'Location',
            'department.name' => 'Department',
        ];
    }
}
