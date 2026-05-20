<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\PartCategory;
use App\Models\Supplier;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PartStockLocation;
use App\Models\StockCount;
use App\Models\User;
use App\Models\UserRole;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display the main inventory management page.
     */
    public function index()
    {
        // Get inventory statistics
        $stats = $this->getInventoryStats();
        
        // Get inventory items
        $items = $this->getInventoryItems();
        
        // Get inventory analytics data
        $analytics = $this->getInventoryAnalytics();
        
        $categories = PartCategory::orderBy('name')->get(['id', 'name']);
        $suppliers  = Supplier::orderBy('name')->get(['id', 'name']);
        $locations  = Part::whereNotNull('storage_location')
            ->distinct()->pluck('storage_location')->filter()->sort()->values();

        return view('inventory', compact('stats', 'items', 'analytics', 'categories', 'suppliers', 'locations'));
    }
    
    /**
     * Get inventory statistics.
     */
    private function getInventoryStats(): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        $sixtyDaysAgo  = now()->subDays(60);

        $totalItems = Part::count();
        $lowStock   = Part::whereNotNull('reorder_point')
            ->whereRaw('current_stock > 0 AND current_stock <= reorder_point')
            ->count();
        $outOfStock = Part::where('current_stock', '<=', 0)->count();
        $totalValue = (float) (Part::selectRaw('SUM(current_stock * COALESCE(average_cost, unit_cost, 0)) as total')
            ->value('total') ?? 0);

        $currNew    = Part::where('created_at', '>=', $thirtyDaysAgo)->count();
        $prevNew    = Part::whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])->count();
        $itemsDelta = $currNew - $prevNew;

        $prevLow  = Part::whereNotNull('reorder_point')
            ->whereRaw('current_stock > 0 AND current_stock <= reorder_point')
            ->where('updated_at', '<', $thirtyDaysAgo)->count();
        $lowDelta = $lowStock - $prevLow;

        $prevOut  = Part::where('current_stock', '<=', 0)->where('updated_at', '<', $thirtyDaysAgo)->count();
        $outDelta = $outOfStock - $prevOut;

        $currVal = (float) DB::table('inventory_transactions')
            ->where('transaction_type', 'receive')
            ->where('performed_at', '>=', $thirtyDaysAgo)->sum('total_cost');
        $prevVal = (float) DB::table('inventory_transactions')
            ->where('transaction_type', 'receive')
            ->whereBetween('performed_at', [$sixtyDaysAgo, $thirtyDaysAgo])->sum('total_cost');

        if ($prevVal > 0) {
            $valPct   = round(($currVal - $prevVal) / $prevVal * 100, 1);
            $valLabel = ($valPct >= 0 ? '+' : '') . $valPct . '%';
            $valColor = $valPct >= 0 ? 'text-green-400' : 'text-red-400';
        } else {
            $valLabel = '—';
            $valColor = 'text-gray-400';
        }

        return [
            'totalItems' => $totalItems,
            'lowStock'   => $lowStock,
            'outOfStock' => $outOfStock,
            'totalValue' => round($totalValue, 2),
            'trends'     => [
                'totalItems' => ['label' => ($itemsDelta >= 0 ? '+' : '') . $itemsDelta, 'color' => 'text-blue-400'],
                'lowStock'   => ['label' => ($lowDelta   >= 0 ? '+' : '') . $lowDelta,   'color' => $lowDelta <= 0 ? 'text-green-400' : 'text-yellow-500'],
                'outOfStock' => ['label' => ($outDelta   >= 0 ? '+' : '') . $outDelta,   'color' => $outDelta <= 0 ? 'text-green-400' : 'text-red-500'],
                'totalValue' => ['label' => $valLabel, 'color' => $valColor],
            ],
        ];
    }
    
    /**
     * Get inventory items from the parts table.
     */
    private function getInventoryItems(): array
    {
        return Part::with(['category', 'supplier'])
            ->orderBy('name')
            ->get()
            ->map(function ($part) {
                $stock        = (float) ($part->getRawOriginal('current_stock') ?? 0);
                $reorderPoint = (float) ($part->getRawOriginal('reorder_point') ?? $part->getRawOriginal('minimum_stock') ?? 0);
                $maxStock     = (float) ($part->getRawOriginal('maximum_stock') ?? 0);
                $unitCost     = (float) ($part->getRawOriginal('unit_cost') ?? $part->getRawOriginal('average_cost') ?? 0);

                $status = match (true) {
                    $stock <= 0                                    => 'OUT_OF_STOCK',
                    $reorderPoint > 0 && $stock <= $reorderPoint  => 'LOW_STOCK',
                    default                                        => 'IN_STOCK',
                };

                return [
                    'id'            => $part->part_number ?? ('INV-' . strtoupper(substr($part->id, 0, 6))),
                    'uuid'          => $part->id,
                    'name'          => $part->name,
                    'category'      => $part->category?->name ?? 'Uncategorized',
                    'sku'           => $part->part_number ?? 'N/A',
                    'quantity'      => (int) $stock,
                    'minStock'      => (int) $reorderPoint,
                    'maxStock'      => (int) $maxStock,
                    'unitPrice'     => round($unitCost, 2),
                    'totalValue'    => round($stock * $unitCost, 2),
                    'supplier'      => $part->supplier?->name ?? 'N/A',
                    'location'      => $part->storage_location ?? $part->bin_location ?? 'N/A',
                    'status'        => $status,
                    'lastRestocked' => $part->updated_at?->format('Y-m-d') ?? 'N/A',
                    'reorderLevel'  => (int) $reorderPoint,
                ];
            })
            ->toArray();
    }
    
    /**
     * Get real inventory analytics: cumulative SKU counts, value by category, weekly usage.
     */
    private function getInventoryAnalytics(): array
    {
        $months = collect(range(5, 0))->map(fn($i) => now()->subMonths($i));

        // Stock Levels: cumulative SKU count registered by end of each month
        $stockLevels = $months->map(
            fn($m) => Part::where('created_at', '<=', $m->copy()->endOfMonth()->toDateTimeString())->count()
        );

        // Value by Category: real valuation from parts table
        $catRows = DB::table('parts')
            ->join('part_categories', 'parts.category_id', '=', 'part_categories.id')
            ->select('part_categories.name as category_name')
            ->selectRaw('ROUND(SUM(parts.current_stock * COALESCE(parts.average_cost, parts.unit_cost, 0)), 2) as category_value')
            ->groupBy('part_categories.id', 'part_categories.name')
            ->orderByDesc('category_value')
            ->limit(6)
            ->get();

        // Weekly Usage: total cost of issued stock per week (last 4 weeks)
        $weeks = collect(range(3, 0))->map(fn($i) => [
            'label' => 'Week ' . (4 - $i),
            'start' => now()->subWeeks($i)->startOfWeek()->toDateTimeString(),
            'end'   => now()->subWeeks($i)->endOfWeek()->toDateTimeString(),
        ]);

        $weeklyUsage = $weeks->map(fn($w) => round(
            DB::table('inventory_transactions')
                ->join('parts', 'inventory_transactions.part_id', '=', 'parts.id')
                ->where('inventory_transactions.transaction_type', 'issue')
                ->whereBetween('inventory_transactions.performed_at', [$w['start'], $w['end']])
                ->selectRaw('SUM(ABS(inventory_transactions.quantity) * COALESCE(parts.average_cost, parts.unit_cost, 0)) as usage_value')
                ->value('usage_value') ?? 0,
            2
        ));

        return [
            'stockLevels' => [
                'labels' => $months->map(fn($m) => $m->format('M'))->values()->toArray(),
                'data'   => $stockLevels->values()->toArray(),
            ],
            'valueByCategory' => [
                'labels' => $catRows->pluck('category_name')->values()->toArray(),
                'data'   => $catRows->pluck('category_value')->map(fn($v) => (float) $v)->values()->toArray(),
            ],
            'monthlyUsage' => [
                'labels' => $weeks->pluck('label')->values()->toArray(),
                'data'   => $weeklyUsage->values()->toArray(),
            ],
        ];
    }
    
    /**
     * Display a listing of parts.
     */
    public function parts(Request $request): JsonResponse
    {
        $query = Part::with(['category', 'supplier', 'manufacturer', 'stockLocations']);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('part_number', 'like', "%{$search}%")
                  ->orWhere('manufacturer_part_number', 'like', "%{$search}%")
                  ->orWhere('supplier_part_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->has('manufacturer_id')) {
            $query->where('manufacturer_id', $request->input('manufacturer_id'));
        }

        if ($request->has('stock_status')) {
            $status = $request->input('stock_status');
            switch ($status) {
                case 'low_stock':
                    $query->lowStock();
                    break;
                case 'needs_reorder':
                    $query->needsReorder();
                    break;
                case 'out_of_stock':
                    $query->where('current_stock', '<=', 0);
                    break;
                case 'overstock':
                    $query->whereRaw('current_stock >= maximum_stock');
                    break;
            }
        }

        if ($request->boolean('hazardous', false)) {
            $query->hazardous();
        }

        if ($request->boolean('expiring', false)) {
            $query->expiring();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $parts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $parts->items(),
            'pagination' => [
                'current_page' => $parts->currentPage(),
                'last_page' => $parts->lastPage(),
                'per_page' => $parts->perPage(),
                'total' => $parts->total(),
                'from' => $parts->firstItem(),
                'to' => $parts->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created part in storage.
     */
    public function storePart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'part_number' => 'required|string|max:100|unique:parts,part_number',
            'manufacturer_part_number' => 'nullable|string|max:100',
            'supplier_part_number' => 'nullable|string|max:100',
            'category_id' => 'nullable|uuid|exists:part_categories,id',
            'manufacturer_id' => 'nullable|uuid|exists:suppliers,id',
            'supplier_id' => 'nullable|uuid|exists:suppliers,id',
            'unit_of_measure' => 'required|string|max:50',
            'current_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:1',
            'shelf_life_days' => 'nullable|integer|min:1',
            'storage_location' => 'nullable|string|max:255',
            'bin_location' => 'nullable|string|max:100',
            'warehouse_location' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'qr_code' => 'nullable|string|max:255',
            'serial_number_required' => 'boolean',
            'batch_number_required' => 'boolean',
            'expiry_date_required' => 'boolean',
            'hazardous_material' => 'boolean',
            'safety_data_sheet_url' => 'nullable|url|max:500',
            'specifications' => 'nullable|array',
            'dimensions' => 'nullable|array',
            'weight_kg' => 'nullable|numeric|min:0',
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

        DB::beginTransaction();
        try {
            $part = Part::create($validated);

            // Create initial stock record if provided
            if (isset($validated['current_stock']) && $validated['current_stock'] > 0) {
                $part->adjustStock(
                    $validated['current_stock'],
                    'Initial stock entry',
                    ['source' => 'part_creation']
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Part created successfully',
                'data' => $part->load(['category', 'supplier', 'manufacturer']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create part',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Display the specified part.
     */
    public function showPart(Part $part): JsonResponse
    {
        $part->load([
            'category',
            'supplier',
            'manufacturer',
            'stockLocations' => function ($query) {
                $query->with('location');
            },
            'inventoryTransactions' => function ($query) {
                $query->with('performer')
                      ->orderBy('performed_at', 'desc')
                      ->limit(20);
            },
            'purchaseOrders' => function ($query) {
                $query->with('supplier')
                      ->orderBy('order_date', 'desc')
                      ->limit(10);
            },
            'workOrders' => function ($query) {
                $query->with(['asset', 'assignedTo'])
                      ->orderBy('created_at', 'desc')
                      ->limit(10);
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => $part,
        ]);
    }

    /**
     * Update the specified part in storage.
     */
    public function updatePart(Request $request, Part $part): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'part_number' => 'sometimes|required|string|max:100|unique:parts,part_number,' . $part->id,
            'manufacturer_part_number' => 'sometimes|nullable|string|max:100',
            'supplier_part_number' => 'sometimes|nullable|string|max:100',
            'category_id' => 'sometimes|nullable|uuid|exists:part_categories,id',
            'manufacturer_id' => 'sometimes|nullable|uuid|exists:suppliers,id',
            'supplier_id' => 'sometimes|nullable|uuid|exists:suppliers,id',
            'unit_of_measure' => 'sometimes|required|string|max:50',
            'minimum_stock' => 'sometimes|nullable|numeric|min:0',
            'maximum_stock' => 'sometimes|nullable|numeric|min:0',
            'reorder_point' => 'sometimes|nullable|numeric|min:0',
            'reorder_quantity' => 'sometimes|nullable|numeric|min:0',
            'unit_cost' => 'sometimes|nullable|numeric|min:0',
            'selling_price' => 'sometimes|nullable|numeric|min:0',
            'lead_time_days' => 'sometimes|nullable|integer|min:1',
            'shelf_life_days' => 'sometimes|nullable|integer|min:1',
            'storage_location' => 'sometimes|nullable|string|max:255',
            'bin_location' => 'sometimes|nullable|string|max:100',
            'warehouse_location' => 'sometimes|nullable|string|max:255',
            'barcode' => 'sometimes|nullable|string|max:255',
            'qr_code' => 'sometimes|nullable|string|max:255',
            'serial_number_required' => 'sometimes|boolean',
            'batch_number_required' => 'sometimes|boolean',
            'expiry_date_required' => 'sometimes|boolean',
            'hazardous_material' => 'sometimes|boolean',
            'safety_data_sheet_url' => 'sometimes|nullable|url|max:500',
            'specifications' => 'sometimes|nullable|array',
            'dimensions' => 'sometimes|nullable|array',
            'weight_kg' => 'sometimes|nullable|numeric|min:0',
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

        $part->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Part updated successfully',
            'data' => $part->fresh()->load(['category', 'supplier', 'manufacturer']),
        ]);
    }

    /**
     * Remove the specified part from storage.
     */
    public function destroyPart(Part $part): JsonResponse
    {
        // Check if part has inventory transactions
        if ($part->inventoryTransactions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete part with inventory transactions',
            ], 422);
        }

        // Check if part is used in purchase orders
        if ($part->purchaseOrders()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete part with purchase orders',
            ], 422);
        }

        $part->delete();

        return response()->json([
            'success' => true,
            'message' => 'Part deleted successfully',
        ]);
    }

    /**
     * Get inventory transactions.
     */
    public function transactions(Request $request): JsonResponse
    {
        $query = InventoryTransaction::with(['part', 'performer']);

        // Apply filters
        if ($request->has('part_id')) {
            $query->where('part_id', $request->input('part_id'));
        }

        if ($request->has('transaction_type')) {
            $type = $request->input('transaction_type');
            if (is_array($type)) {
                $query->whereIn('transaction_type', $type);
            } else {
                $query->where('transaction_type', $type);
            }
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('reference')) {
            $query->where('reference', 'like', '%' . $request->input('reference') . '%');
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('performed_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('performed_at', '<=', $request->input('date_to'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'performed_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Create inventory transaction.
     */
    public function createTransaction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'part_id' => 'required|uuid|exists:parts,id',
            'quantity' => 'required|numeric',
            'transaction_type' => 'required|in:purchase,receive,issue,return,transfer,adjustment,reservation,release_reservation,damage,loss,expired,recall',
            'reference' => 'nullable|string|max:255',
            'reference_type' => 'nullable|string|max:50',
            'unit_cost' => 'nullable|numeric|min:0',
            'batch_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'serial_numbers' => 'nullable|array',
            'location_from' => 'nullable|string|max:255',
            'location_to' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['performed_by'] = auth()->id();
        $validated['performed_at'] = now();

        DB::beginTransaction();
        try {
            $part = Part::findOrFail($validated['part_id']);
            
            // Create inventory transaction
            $transaction = $part->updateStock(
                $validated['quantity'],
                $validated['transaction_type'],
                $validated['reference'] ?? null,
                array_intersect_key($validated, array_flip([
                    'unit_cost', 'batch_number', 'expiry_date', 'serial_numbers',
                    'location_from', 'location_to', 'notes', 'metadata'
                ]))
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => $transaction->load(['part', 'performer']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Update stock for a part (called from the inventory page).
     */
    public function updateStock(Request $request, string $itemId): JsonResponse
    {
        $part = Part::findOrFail($itemId);

        $validator = Validator::make($request->all(), [
            'quantity'         => 'required|numeric|not_in:0',
            'transaction_type' => 'required|in:receive,purchase,return,adjustment',
            'unit_cost'        => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $metadata  = array_filter([
            'unit_cost' => $validated['unit_cost'] ?? null,
            'notes'     => $validated['notes'] ?? null,
        ]);

        try {
            $part->updateStock(
                (float) $validated['quantity'],
                $validated['transaction_type'],
                $validated['notes'] ?? null,
                $metadata
            );

            $part->refresh();
            $newStock     = (float) ($part->getRawOriginal('current_stock') ?? 0);
            $reorderPoint = (float) ($part->getRawOriginal('reorder_point') ?? 0);

            $status = match (true) {
                $newStock <= 0                                   => 'OUT_OF_STOCK',
                $reorderPoint > 0 && $newStock <= $reorderPoint => 'LOW_STOCK',
                default                                          => 'IN_STOCK',
            };

            return response()->json([
                'success'      => true,
                'message'      => 'Stock updated successfully',
                'new_quantity' => (int) $newStock,
                'new_status'   => $status,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get purchase orders.
     */
    public function purchaseOrders(Request $request): JsonResponse
    {
        $query = PurchaseOrder::with(['supplier', 'items.part', 'creator', 'approver']);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($subQuery) use ($search) {
                      $subQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('order_date', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('order_date', '<=', $request->input('date_to'));
        }

        // Special filters
        if ($request->boolean('overdue', false)) {
            $query->overdue();
        }

        if ($request->boolean('pending', false)) {
            $query->pending();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'order_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Create purchase order.
     */
    public function createPurchaseOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'priority' => 'required|in:low,normal,high,urgent,critical',
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|uuid|exists:parts,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $items = $validated['items'];
        unset($validated['items']);

        DB::beginTransaction();
        try {
            // Generate order number
            $validated['order_number'] = $this->generateOrderNumber();
            $validated['order_date'] = today();
            $validated['created_by'] = auth()->id();

            $order = PurchaseOrder::create($validated);

            // Create purchase order items
            $subtotal = 0;
            foreach ($items as $itemData) {
                $totalCost = $itemData['quantity'] * $itemData['unit_cost'];
                $subtotal += $totalCost;

                $order->items()->create([
                    'part_id' => $itemData['part_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'],
                    'total_cost' => $totalCost,
                    'notes' => $itemData['notes'] ?? null,
                    'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                ]);
            }

            // Calculate totals
            $taxAmount = $subtotal * 0.1; // 10% tax rate
            $totalAmount = $subtotal + $taxAmount;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'data' => $order->load(['supplier', 'items.part']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Get inventory statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'parts' => [
                'total' => Part::count(),
                'active' => Part::active()->count(),
                'low_stock' => Part::lowStock()->count(),
                'needs_reorder' => Part::needsReorder()->count(),
                'out_of_stock' => Part::where('current_stock', '<=', 0)->count(),
                'hazardous' => Part::hazardous()->count(),
                'expiring' => Part::expiring()->count(),
            ],
            'inventory_value' => [
                'total_value' => Part::sum(DB::raw('current_stock * average_cost')),
                'low_stock_value' => Part::lowStock()->sum(DB::raw('current_stock * average_cost')),
                'overstock_value' => Part::whereRaw('current_stock >= maximum_stock')->sum(DB::raw('current_stock * average_cost')),
            ],
            'purchase_orders' => [
                'total' => PurchaseOrder::count(),
                'draft' => PurchaseOrder::where('status', 'draft')->count(),
                'pending' => PurchaseOrder::where('status', 'pending')->count(),
                'approved' => PurchaseOrder::where('status', 'approved')->count(),
                'ordered' => PurchaseOrder::where('status', 'ordered')->count(),
                'shipped' => PurchaseOrder::where('status', 'shipped')->count(),
                'received' => PurchaseOrder::where('status', 'received')->count(),
                'cancelled' => PurchaseOrder::where('status', 'cancelled')->count(),
                'overdue' => PurchaseOrder::overdue()->count(),
            ],
            'transactions' => [
                'today' => InventoryTransaction::whereDate('performed_at', today())->count(),
                'this_week' => InventoryTransaction::whereBetween('performed_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'this_month' => InventoryTransaction::whereMonth('performed_at', now()->month)
                    ->whereYear('performed_at', now()->year)
                    ->count(),
                'by_type' => InventoryTransaction::select('transaction_type', DB::raw('count(*) as count'))
                    ->groupBy('transaction_type')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->transaction_type => $item->count];
                    }),
            ],
            'suppliers' => [
                'total' => Supplier::count(),
                'active' => Supplier::active()->count(),
                'manufacturers' => Supplier::manufacturers()->count(),
                'suppliers_only' => Supplier::suppliersOnly()->count(),
            ],
            'categories' => [
                'total' => PartCategory::count(),
                'active' => PartCategory::active()->count(),
                'root' => PartCategory::root()->count(),
                'with_parts' => PartCategory::has('parts')->count(),
            ],
            'stock_locations' => [
                'total_locations' => PartStockLocation::distinct('location_id')->count('location_id'),
                'total_stock_records' => PartStockLocation::count(),
                'low_stock_locations' => PartStockLocation::lowStock()->count(),
            ],
            'recent_activity' => [
                'recent_transactions' => InventoryTransaction::with(['part', 'performer'])
                    ->orderBy('performed_at', 'desc')
                    ->limit(5)
                    ->get(),
                'recent_purchase_orders' => PurchaseOrder::with(['supplier'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
                'parts_needing_attention' => Part::needsReorder()
                    ->with(['category', 'supplier'])
                    ->orderBy('current_stock', 'asc')
                    ->limit(5)
                    ->get(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get stock forecast.
     */
    public function stockForecast(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'part_id' => 'required|uuid|exists:parts,id',
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $days = $validated['days'] ?? 90;
        $part = Part::findOrFail($validated['part_id']);

        $forecast = $part->getStockForecast($days);
        $usageStats = $part->getStockUsageStats(30);

        return response()->json([
            'success' => true,
            'data' => [
                'part' => $part->only(['id', 'name', 'part_number', 'current_stock', 'minimum_stock', 'reorder_point']),
                'usage_stats' => $usageStats,
                'forecast' => $forecast,
                'recommendations' => $this->generateStockRecommendations($part, $usageStats, $forecast),
            ],
        ]);
    }

    /**
     * Get low stock alerts.
     */
    public function lowStockAlerts(): JsonResponse
    {
        $lowStockParts = Part::lowStock()
            ->with(['category', 'supplier'])
            ->orderBy('current_stock', 'asc')
            ->get();

        $alerts = $lowStockParts->map(function ($part) {
            return [
                'id' => $part->id,
                'name' => $part->name,
                'part_number' => $part->part_number,
                'current_stock' => $part->current_stock,
                'minimum_stock' => $part->minimum_stock,
                'reorder_point' => $part->reorder_point,
                'shortage' => $part->minimum_stock - $part->current_stock,
                'category' => $part->category?->name,
                'supplier' => $part->supplier?->name,
                'reorder_quantity' => $part->reorder_quantity,
                'reorder_value' => $part->reorder_value,
                'stock_level_status' => $part->stock_level_status_display,
                'stock_level_color' => $part->stock_level_status_color,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Generate order number.
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ym');
        $sequence = PurchaseOrder::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Generate stock recommendations.
     */
    private function generateStockRecommendations(Part $part, array $usageStats, array $forecast): array
    {
        $recommendations = [];

        // Current stock analysis
        if ($part->isOutOfStock()) {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'Part is currently out of stock',
                'action' => 'Immediate reorder required',
                'priority' => 'urgent',
            ];
        } elseif ($part->isLowStock()) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Stock level is below minimum',
                'action' => 'Reorder recommended',
                'priority' => 'high',
            ];
        } elseif ($part->needsReordering()) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'Stock has reached reorder point',
                'action' => 'Create purchase order',
                'priority' => 'normal',
            ];
        }

        // Usage analysis
        if ($usageStats['average_daily_usage'] > 0) {
            $daysOfStock = $usageStats['days_of_stock'];
            
            if ($daysOfStock < 7) {
                $recommendations[] = [
                    'type' => 'warning',
                    'message' => sprintf('Only %.1f days of stock remaining', $daysOfStock),
                    'action' => 'Consider expedited shipping',
                    'priority' => 'high',
                ];
            } elseif ($daysOfStock > 90) {
                $recommendations[] = [
                    'type' => 'info',
                    'message' => sprintf('%.1f days of stock on hand', $daysOfStock),
                    'action' => 'Consider reducing order quantity',
                    'priority' => 'low',
                ];
            }
        }

        // Forecast analysis
        $outOfStockDate = null;
        foreach ($forecast as $day) {
            if ($day['projected_stock'] <= 0) {
                $outOfStockDate = $day['date'];
                break;
            }
        }

        if ($outOfStockDate) {
            $recommendations[] = [
                'type' => 'critical',
                'message' => "Projected out of stock on {$outOfStockDate}",
                'action' => 'Schedule reorder before this date',
                'priority' => 'urgent',
            ];
        }

        return $recommendations;
    }

    /**
     * Return full details for a single inventory item (AJAX).
     */
    public function getItemDetails(string $itemId): JsonResponse
    {
        try {
            $part = Part::with(['category', 'supplier'])->findOrFail($itemId);

            $recentTransactions = $part->inventoryTransactions()
                ->orderByDesc('performed_at')
                ->limit(5)
                ->get()
                ->map(function ($t) {
                    $type = $t->transaction_type instanceof \BackedEnum
                        ? $t->transaction_type->value
                        : (string) $t->transaction_type;
                    return [
                        'type'     => $type,
                        'quantity' => (float) $t->getRawOriginal('quantity'),
                        'date'     => $t->performed_at?->format('Y-m-d') ?? 'N/A',
                        'notes'    => $t->notes ?? '',
                    ];
                });

            $stock        = (float) ($part->getRawOriginal('current_stock') ?? 0);
            $reorderPoint = (float) ($part->getRawOriginal('reorder_point') ?? 0);
            $unitCost     = (float) ($part->getRawOriginal('unit_cost') ?? $part->getRawOriginal('average_cost') ?? 0);

            $status = match (true) {
                $stock <= 0                                    => 'OUT_OF_STOCK',
                $reorderPoint > 0 && $stock <= $reorderPoint  => 'LOW_STOCK',
                default                                        => 'IN_STOCK',
            };

            return response()->json([
                'success'     => true,
                'id'          => $part->part_number ?? ('INV-' . strtoupper(substr($part->id, 0, 6))),
                'uuid'        => $part->id,
                'name'        => $part->name,
                'description' => $part->description ?? '',
                'category'    => $part->category?->name ?? 'Uncategorized',
                'sku'         => $part->part_number ?? 'N/A',
                'quantity'    => (int) $stock,
                'minStock'    => (int)(float)($part->getRawOriginal('minimum_stock') ?? 0),
                'maxStock'    => (int)(float)($part->getRawOriginal('maximum_stock') ?? 0),
                'reorderPoint'=> (int) $reorderPoint,
                'unitCost'    => round($unitCost, 2),
                'avgCost'     => round((float)($part->getRawOriginal('average_cost') ?? 0), 2),
                'totalValue'  => round($stock * $unitCost, 2),
                'supplier'    => $part->supplier?->name ?? 'N/A',
                'location'    => $part->storage_location ?? $part->bin_location ?? 'N/A',
                'unitOfMeasure' => $part->unit_of_measure ?? 'unit',
                'leadTimeDays'  => $part->lead_time_days ?? 0,
                'notes'         => $part->notes ?? '',
                'status'        => $status,
                'lastRestocked' => $part->updated_at?->format('Y-m-d') ?? 'N/A',
                'recentTransactions' => $recentTransactions,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new inventory item (Manager+).
     */
    public function createItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'part_number'      => 'nullable|string|max:100|unique:parts,part_number',
            'category_id'      => 'nullable|string|exists:part_categories,id',
            'supplier_id'      => 'nullable|string|exists:suppliers,id',
            'unit_of_measure'  => 'nullable|string|max:50',
            'current_stock'    => 'nullable|numeric|min:0',
            'minimum_stock'    => 'nullable|numeric|min:0',
            'reorder_point'    => 'nullable|numeric|min:0',
            'unit_cost'        => 'nullable|numeric|min:0',
            'storage_location' => 'nullable|string|max:255',
            'description'      => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $data = array_filter($validator->validated(), fn($v) => !is_null($v));
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();
            $data['is_active']  = true;

            $part = Part::create($data);
            $part->load(['category', 'supplier']);

            $stock        = (float) ($part->getRawOriginal('current_stock') ?? 0);
            $reorderPoint = (float) ($part->getRawOriginal('reorder_point') ?? 0);
            $unitCost     = (float) ($part->getRawOriginal('unit_cost') ?? 0);

            $status = match (true) {
                $stock <= 0                                    => 'OUT_OF_STOCK',
                $reorderPoint > 0 && $stock <= $reorderPoint  => 'LOW_STOCK',
                default                                        => 'IN_STOCK',
            };

            return response()->json([
                'success' => true,
                'message' => 'Item created successfully',
                'item'    => [
                    'id'           => $part->part_number ?? ('INV-' . strtoupper(substr($part->id, 0, 6))),
                    'uuid'         => $part->id,
                    'name'         => $part->name,
                    'category'     => $part->category?->name ?? 'Uncategorized',
                    'sku'          => $part->part_number ?? 'N/A',
                    'quantity'     => (int) $stock,
                    'minStock'     => (int)(float)($part->getRawOriginal('minimum_stock') ?? 0),
                    'maxStock'     => (int)(float)($part->getRawOriginal('maximum_stock') ?? 0),
                    'unitPrice'    => round($unitCost, 2),
                    'totalValue'   => round($stock * $unitCost, 2),
                    'supplier'     => $part->supplier?->name ?? 'N/A',
                    'location'     => $part->storage_location ?? 'N/A',
                    'status'       => $status,
                    'lastRestocked'=> $part->updated_at?->format('Y-m-d') ?? 'N/A',
                    'reorderLevel' => (int) $reorderPoint,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Stream a CSV export of all inventory items (Auditor+).
     */
    public function exportInventory(Request $request): StreamedResponse
    {
        $filename = 'inventory_export_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Item ID', 'Name', 'Category', 'SKU', 'Quantity',
                'Min Stock', 'Reorder Point', 'Unit Cost (UGX)',
                'Total Value (UGX)', 'Supplier', 'Location', 'Status', 'Last Updated',
            ]);

            Part::with(['category', 'supplier'])->orderBy('name')->chunk(200, function ($parts) use ($handle) {
                foreach ($parts as $part) {
                    $stock        = (float) ($part->getRawOriginal('current_stock') ?? 0);
                    $reorderPoint = (float) ($part->getRawOriginal('reorder_point') ?? 0);
                    $minStock     = (float) ($part->getRawOriginal('minimum_stock') ?? 0);
                    $unitCost     = (float) ($part->getRawOriginal('unit_cost') ?? $part->getRawOriginal('average_cost') ?? 0);

                    $status = match (true) {
                        $stock <= 0                                    => 'OUT_OF_STOCK',
                        $reorderPoint > 0 && $stock <= $reorderPoint  => 'LOW_STOCK',
                        default                                        => 'IN_STOCK',
                    };

                    fputcsv($handle, [
                        $part->part_number ?? ('INV-' . strtoupper(substr($part->id, 0, 6))),
                        $part->name,
                        $part->category?->name ?? 'Uncategorized',
                        $part->part_number ?? 'N/A',
                        (int) $stock,
                        (int) $minStock,
                        (int) $reorderPoint,
                        round($unitCost, 2),
                        round($stock * $unitCost, 2),
                        $part->supplier?->name ?? 'N/A',
                        $part->storage_location ?? $part->bin_location ?? 'N/A',
                        $status,
                        $part->updated_at?->format('Y-m-d') ?? 'N/A',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate a summary inventory report (Auditor+).
     */
    public function generateReport(): JsonResponse
    {
        try {
            $stats = $this->getInventoryStats();

            $topItems = Part::with(['category'])
                ->selectRaw('*, (current_stock * COALESCE(average_cost, unit_cost, 0)) as computed_value')
                ->orderByRaw('(current_stock * COALESCE(average_cost, unit_cost, 0)) DESC')
                ->limit(10)
                ->get()
                ->map(function ($part) {
                    return [
                        'name'       => $part->name,
                        'sku'        => $part->part_number ?? 'N/A',
                        'category'   => $part->category?->name ?? 'Uncategorized',
                        'quantity'   => (int)(float)($part->getRawOriginal('current_stock') ?? 0),
                        'totalValue' => round(
                            (float)($part->getRawOriginal('current_stock') ?? 0) *
                            (float)($part->getRawOriginal('average_cost') ?? $part->getRawOriginal('unit_cost') ?? 0),
                            2
                        ),
                    ];
                });

            $lowStockItems = Part::whereNotNull('reorder_point')
                ->whereRaw('current_stock > 0 AND current_stock <= reorder_point')
                ->orderBy('current_stock')
                ->limit(10)
                ->get(['name', 'part_number', 'current_stock', 'reorder_point'])
                ->map(fn($p) => [
                    'name'    => $p->name,
                    'sku'     => $p->part_number ?? 'N/A',
                    'current' => (int)(float)($p->getRawOriginal('current_stock') ?? 0),
                    'reorder' => (int)(float)($p->getRawOriginal('reorder_point') ?? 0),
                ]);

            return response()->json([
                'success'        => true,
                'generated_at'   => now()->format('Y-m-d H:i:s'),
                'stats'          => $stats,
                'top_value_items'=> $topItems,
                'low_stock_items'=> $lowStockItems,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
