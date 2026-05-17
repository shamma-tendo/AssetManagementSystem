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
        
        return view('inventory', compact('stats', 'items', 'analytics'));
    }
    
    /**
     * Get inventory statistics.
     */
    private function getInventoryStats()
    {
        return [
            'totalItems' => 342,
            'lowStock' => 12,
            'outOfStock' => 3,
            'totalValue' => 284750.50,
        ];
    }
    
    /**
     * Get inventory items.
     */
    private function getInventoryItems()
    {
        return [
            [
                'id' => 'INV-001',
                'name' => 'Industrial Bearings 6205-2RS',
                'category' => 'Mechanical Parts',
                'sku' => 'BRG-6205-2RS',
                'quantity' => 245,
                'minStock' => 50,
                'maxStock' => 500,
                'unitPrice' => 45.75,
                'totalValue' => 11208.75,
                'supplier' => 'TechParts Inc.',
                'location' => 'Warehouse A - Shelf 12',
                'status' => 'IN_STOCK',
                'lastRestocked' => '2024-05-10',
                'reorderLevel' => 50
            ],
            [
                'id' => 'INV-002',
                'name' => 'Hydraulic Oil ISO VG 46',
                'category' => 'Fluids & Lubricants',
                'sku' => 'HYD-OIL-46',
                'quantity' => 15,
                'minStock' => 25,
                'maxStock' => 100,
                'unitPrice' => 125.50,
                'totalValue' => 1882.50,
                'supplier' => 'FluidTech Solutions',
                'location' => 'Storage Tank B',
                'status' => 'LOW_STOCK',
                'lastRestocked' => '2024-04-28',
                'reorderLevel' => 25
            ],
            [
                'id' => 'INV-003',
                'name' => 'Temperature Sensor PT100',
                'category' => 'Electronics',
                'sku' => 'SENS-PT100',
                'quantity' => 0,
                'minStock' => 20,
                'maxStock' => 100,
                'unitPrice' => 89.99,
                'totalValue' => 0.00,
                'supplier' => 'SensorTech Pro',
                'location' => 'Electronics Cabinet C',
                'status' => 'OUT_OF_STOCK',
                'lastRestocked' => '2024-04-15',
                'reorderLevel' => 20
            ],
            [
                'id' => 'INV-004',
                'name' => 'Conveyor Belt 500mm x 10m',
                'category' => 'Conveyor Systems',
                'sku' => 'CNV-BELT-500',
                'quantity' => 8,
                'minStock' => 5,
                'maxStock' => 20,
                'unitPrice' => 342.00,
                'totalValue' => 2736.00,
                'supplier' => 'BeltMaster Corp',
                'location' => 'Warehouse B - Rack 3',
                'status' => 'IN_STOCK',
                'lastRestocked' => '2024-05-08',
                'reorderLevel' => 5
            ],
            [
                'id' => 'INV-005',
                'name' => 'Electric Motor 5HP 3Phase',
                'category' => 'Motors & Drives',
                'sku' => 'MOT-5HP-3PH',
                'quantity' => 3,
                'minStock' => 2,
                'maxStock' => 10,
                'unitPrice' => 1250.00,
                'totalValue' => 3750.00,
                'supplier' => 'MotorWorks Ltd',
                'location' => 'Motor Storage D',
                'status' => 'IN_STOCK',
                'lastRestocked' => '2024-05-05',
                'reorderLevel' => 2
            ],
            [
                'id' => 'INV-006',
                'name' => 'Pressure Relief Valve 1/2"',
                'category' => 'Valves & Fittings',
                'sku' => 'VALVE-PR-012',
                'quantity' => 45,
                'minStock' => 30,
                'maxStock' => 150,
                'unitPrice' => 67.25,
                'totalValue' => 3026.25,
                'supplier' => 'ValveTech International',
                'location' => 'Valves Section E',
                'status' => 'IN_STOCK',
                'lastRestocked' => '2024-05-09',
                'reorderLevel' => 30
            ],
            [
                'id' => 'INV-007',
                'name' => 'LED Flood Light 150W',
                'category' => 'Lighting',
                'sku' => 'LED-FLD-150',
                'quantity' => 18,
                'minStock' => 25,
                'maxStock' => 100,
                'unitPrice' => 95.50,
                'totalValue' => 1719.00,
                'supplier' => 'LightTech Solutions',
                'location' => 'Lighting Storage F',
                'status' => 'LOW_STOCK',
                'lastRestocked' => '2024-04-20',
                'reorderLevel' => 25
            ],
            [
                'id' => 'INV-008',
                'name' => 'Circuit Breaker 3P 100A',
                'category' => 'Electrical',
                'sku' => 'CB-3P-100A',
                'quantity' => 12,
                'minStock' => 10,
                'maxStock' => 50,
                'unitPrice' => 145.00,
                'totalValue' => 1740.00,
                'supplier' => 'ElectroSupply Co',
                'location' => 'Electrical Panel G',
                'status' => 'IN_STOCK',
                'lastRestocked' => '2024-05-11',
                'reorderLevel' => 10
            ],
            [
                'id' => 'INV-009',
                'name' => 'Pneumatic Cylinder 50mm Stroke',
                'category' => 'Pneumatics',
                'sku' => 'PNEU-CYL-50',
                'quantity' => 6,
                'minStock' => 8,
                'maxStock' => 30,
                'unitPrice' => 225.75,
                'totalValue' => 1354.50,
                'supplier' => 'PneuTech Systems',
                'location' => 'Pneumatics Bay H',
                'status' => 'LOW_STOCK',
                'lastRestocked' => '2024-04-25',
                'reorderLevel' => 8
            ],
            [
                'id' => 'INV-010',
                'name' => 'Steel Plate 10mm x 1m x 2m',
                'category' => 'Raw Materials',
                'sku' => 'STEEL-10MM',
                'quantity' => 24,
                'minStock' => 15,
                'maxStock' => 50,
                'unitPrice' => 180.00,
                'totalValue' => 4320.00,
                'supplier' => 'MetalWorks Supply',
                'location' => 'Raw Materials Yard',
                'status' => 'IN_STOCK',
                'lastRestocked' => '2024-05-07',
                'reorderLevel' => 15
            ],
            [
                'id' => 'INV-011',
                'name' => 'Filter Element 5 Micron',
                'category' => 'Filters',
                'sku' => 'FILT-5MIC',
                'quantity' => 0,
                'minStock' => 40,
                'maxStock' => 200,
                'unitPrice' => 18.50,
                'totalValue' => 0.00,
                'supplier' => 'FilterTech Pro',
                'location' => 'Filter Storage I',
                'status' => 'OUT_OF_STOCK',
                'lastRestocked' => '2024-04-10',
                'reorderLevel' => 40
            ],
            [
                'id' => 'INV-012',
                'name' => 'Gearbox Reducer 10:1',
                'category' => 'Mechanical Parts',
                'sku' => 'GEAR-10-1',
                'quantity' => 4,
                'minStock' => 3,
                'maxStock' => 15,
                'unitPrice' => 890.00,
                'totalValue' => 3560.00,
                'supplier' => 'GearTech Solutions',
                'location' => 'Mechanical Parts J',
                'status' => 'IN_STOCK',
                'lastRestocked' => '2024-05-06',
                'reorderLevel' => 3
            ]
        ];
    }
    
    /**
     * Get inventory analytics data.
     */
    private function getInventoryAnalytics()
    {
        return [
            'stockLevels' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'data' => [285, 298, 312, 295, 342, 328]
            ],
            'valueByCategory' => [
                'labels' => ['Mechanical Parts', 'Fluids & Lubricants', 'Electronics', 'Motors & Drives', 'Valves & Fittings', 'Other'],
                'data' => [45750, 28500, 32100, 38900, 27800, 11200]
            ],
            'monthlyUsage' => [
                'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                'data' => [12450, 15800, 11200, 18900]
            ]
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
}
