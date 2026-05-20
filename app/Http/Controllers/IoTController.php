<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Sensor;
use App\Models\SensorType;
use App\Models\SensorReading;
use App\Models\SensorAlert;
use App\Models\SensorCalibration;
use App\Models\SensorAlertTemplate;
use App\Models\User;
use App\Models\UserRole;
use App\Services\IoTService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IoTController extends Controller
{
    protected IoTService $iotService;

    public function __construct(IoTService $iotService)
    {
        $this->iotService = $iotService;
    }

    /**
     * Display a listing of sensors.
     */
    public function sensors(Request $request): JsonResponse
    {
        $query = Sensor::with(['asset', 'sensorType', 'latestReading']);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->input('asset_id'));
        }

        if ($request->has('sensor_type_id')) {
            $query->where('sensor_type_id', $request->input('sensor_type_id'));
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }

        if ($request->has('health_status')) {
            $healthStatus = $request->input('health_status');
            $query->whereHas('latestReading', function ($q) use ($healthStatus) {
                // This would need to be implemented based on health status logic
            });
        }

        // Special filters
        if ($request->boolean('needs_calibration', false)) {
            $query->needsCalibration();
        }

        if ($request->boolean('low_battery', false)) {
            $query->lowBattery();
        }

        if ($request->boolean('offline', false)) {
            $query->offline();
        }

        if ($request->boolean('poor_signal', false)) {
            $query->poorSignal();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $sensors = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $sensors->items(),
            'pagination' => [
                'current_page' => $sensors->currentPage(),
                'last_page' => $sensors->lastPage(),
                'per_page' => $sensors->perPage(),
                'total' => $sensors->total(),
                'from' => $sensors->firstItem(),
                'to' => $sensors->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created sensor in storage.
     */
    public function storeSensor(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|uuid|exists:assets,id',
            'sensor_type_id' => 'required|uuid|exists:sensor_types,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'firmware_version' => 'nullable|string|max:50',
            'hardware_version' => 'nullable|string|max:50',
            'mac_address' => 'nullable|string|max:17',
            'ip_address' => 'nullable|ip|max:45',
            'location_description' => 'nullable|string|max:500',
            'installation_date' => 'nullable|date',
            'calibration_date' => 'nullable|date',
            'next_calibration_date' => 'nullable|date|after:calibration_date',
            'configuration' => 'nullable|array',
            'threshold_min' => 'nullable|numeric',
            'threshold_max' => 'nullable|numeric',
            'alert_enabled' => 'boolean',
            'data_retention_days' => 'nullable|integer|min:1',
            'sampling_interval' => 'nullable|integer|min:1',
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

        // Set default values
        $validated['status'] = SensorStatus::ACTIVE;
        $validated['alert_enabled'] = $validated['alert_enabled'] ?? true;
        $validated['data_retention_days'] = $validated['data_retention_days'] ?? 90;

        DB::beginTransaction();
        try {
            $sensor = Sensor::create($validated);

            // Set default thresholds from sensor type if not provided
            if (!$validated['threshold_min'] && $sensor->sensorType->default_threshold_min) {
                $sensor->threshold_min = $sensor->sensorType->default_threshold_min;
            }
            if (!$validated['threshold_max'] && $sensor->sensorType->default_threshold_max) {
                $sensor->threshold_max = $sensor->sensorType->default_threshold_max;
            }
            $sensor->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sensor created successfully',
                'data' => $sensor->load(['asset', 'sensorType']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sensor',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Display the specified sensor.
     */
    public function showSensor(Sensor $sensor): JsonResponse
    {
        $sensor->load([
            'asset',
            'sensorType',
            'readings' => function ($query) {
                $query->latest('timestamp')->limit(100);
            },
            'alerts' => function ($query) {
                $query->latest('triggered_at')->limit(50);
            },
            'calibrationRecords' => function ($query) {
                $query->latest('calibration_date');
            },
            'creator',
            'updater',
        ]);

        return response()->json([
            'success' => true,
            'data' => $sensor,
        ]);
    }

    /**
     * Update the specified sensor in storage.
     */
    public function updateSensor(Request $request, Sensor $sensor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'manufacturer' => 'sometimes|nullable|string|max:255',
            'model' => 'sometimes|nullable|string|max:255',
            'firmware_version' => 'sometimes|nullable|string|max:50',
            'hardware_version' => 'sometimes|nullable|string|max:50',
            'mac_address' => 'sometimes|nullable|string|max:17',
            'ip_address' => 'sometimes|nullable|ip|max:45',
            'location_description' => 'sometimes|nullable|string|max:500',
            'installation_date' => 'sometimes|nullable|date',
            'calibration_date' => 'sometimes|nullable|date',
            'next_calibration_date' => 'sometimes|nullable|date|after:calibration_date',
            'configuration' => 'sometimes|nullable|array',
            'threshold_min' => 'sometimes|nullable|numeric',
            'threshold_max' => 'sometimes|nullable|numeric',
            'alert_enabled' => 'sometimes|boolean',
            'data_retention_days' => 'sometimes|nullable|integer|min:1',
            'sampling_interval' => 'sometimes|nullable|integer|min:1',
            'status' => 'sometimes|in:active,inactive,maintenance,error,calibrating,offline',
            'notes' => 'sometimes|nullable|string',
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

        $sensor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sensor updated successfully',
            'data' => $sensor->fresh()->load(['asset', 'sensorType']),
        ]);
    }

    /**
     * Remove the specified sensor from storage.
     */
    public function destroySensor(Sensor $sensor): JsonResponse
    {
        // Check if sensor has readings
        if ($sensor->readings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete sensor with readings',
            ], 422);
        }

        $sensor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sensor deleted successfully',
        ]);
    }

    /**
     * Get sensor readings.
     */
    public function readings(Request $request): JsonResponse
    {
        $query = SensorReading::with(['sensor.asset', 'sensor.sensorType']);

        // Apply filters
        if ($request->has('sensor_id')) {
            $query->where('sensor_id', $request->input('sensor_id'));
        }

        if ($request->has('asset_id')) {
            $query->whereHas('sensor', function ($q) use ($request) {
                $q->where('asset_id', $request->input('asset_id'));
            });
        }

        if ($request->has('sensor_type_id')) {
            $query->whereHas('sensor', function ($q) use ($request) {
                $q->where('sensor_type_id', $request->input('sensor_type_id'));
            });
        }

        // Date range filter
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('timestamp', [
                $request->input('date_from'),
                $request->input('date_to')
            ]);
        }

        // Time-based filters
        if ($request->has('hours')) {
            $query->lastHours($request->input('hours'));
        } elseif ($request->has('days')) {
            $query->lastDays($request->input('days'));
        }

        // Quality filter
        if ($request->has('quality')) {
            $quality = $request->input('quality');
            if ($quality === 'good') {
                $query->goodQuality();
            } elseif ($quality === 'poor') {
                $query->poorQuality();
            }
        }

        // Error filter
        if ($request->boolean('with_errors', false)) {
            $query->withErrors();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'timestamp');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $readings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $readings->items(),
            'pagination' => [
                'current_page' => $readings->currentPage(),
                'last_page' => $readings->lastPage(),
                'per_page' => $readings->perPage(),
                'total' => $readings->total(),
            ],
        ]);
    }

    /**
     * Create sensor reading.
     */
    public function createReading(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sensor_id' => 'required|uuid|exists:sensors,id',
            'value' => 'required|numeric',
            'timestamp' => 'nullable|date',
            'unit' => 'nullable|string|max:50',
            'quality' => 'nullable|numeric|min:0|max:1',
            'raw_data' => 'nullable|array',
            'metadata' => 'nullable|array',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'signal_strength' => 'nullable|integer|min:0|max:100',
            'temperature' => 'nullable|numeric',
            'humidity' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['timestamp'] = $validated['timestamp'] ?? now();

        try {
            $reading = SensorReading::createValidated($validated);

            return response()->json([
                'success' => true,
                'message' => 'Reading created successfully',
                'data' => $reading->load(['sensor.asset', 'sensor.sensorType']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reading',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Get sensor alerts.
     */
    public function alerts(Request $request): JsonResponse
    {
        $query = SensorAlert::with(['sensor.asset', 'sensor.sensorType', 'acknowledger', 'resolver']);

        // Apply filters
        if ($request->has('sensor_id')) {
            $query->where('sensor_id', $request->input('sensor_id'));
        }

        if ($request->has('asset_id')) {
            $query->whereHas('sensor', function ($q) use ($request) {
                $q->where('asset_id', $request->input('asset_id'));
            });
        }

        if ($request->has('alert_type')) {
            $query->where('alert_type', $request->input('alert_type'));
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        // Status filters
        if ($request->boolean('unacknowledged', false)) {
            $query->unacknowledged();
        }

        if ($request->boolean('unresolved', false)) {
            $query->unresolved();
        }

        if ($request->boolean('active', false)) {
            $query->unacknowledged()->unresolved();
        }

        // Date range filter
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->dateRange($request->input('date_from'), $request->input('date_to'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'triggered_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $alerts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $alerts->items(),
            'pagination' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ],
        ]);
    }

    /**
     * Acknowledge sensor alert.
     */
    public function acknowledgeAlert(Request $request, SensorAlert $alert): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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

        $alert->acknowledge(auth()->user(), $validated['notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged successfully',
            'data' => $alert->fresh()->load(['sensor.asset', 'acknowledger']),
        ]);
    }

    /**
     * Resolve sensor alert.
     */
    public function resolveAlert(Request $request, SensorAlert $alert): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resolution_notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $alert->resolve(auth()->user(), $validated['resolution_notes']);

        return response()->json([
            'success' => true,
            'message' => 'Alert resolved successfully',
            'data' => $alert->fresh()->load(['sensor.asset', 'resolver']),
        ]);
    }

    /**
     * Get sensor types.
     */
    public function sensorTypes(): JsonResponse
    {
        $types = SensorType::active()->get();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Get sensor calibrations.
     */
    public function calibrations(Request $request): JsonResponse
    {
        $query = SensorCalibration::with(['sensor.asset', 'sensor.sensorType', 'performer', 'approver']);

        // Apply filters
        if ($request->has('sensor_id')) {
            $query->where('sensor_id', $request->input('sensor_id'));
        }

        if ($request->has('calibration_type')) {
            $query->where('calibration_type', $request->input('calibration_type'));
        }

        if ($request->has('calibration_status')) {
            $query->where('calibration_status', $request->input('calibration_status'));
        }

        if ($request->boolean('pending', false)) {
            $query->pending();
        }

        if ($request->boolean('approved', false)) {
            $query->approved();
        }

        // Date range filter
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->dateRange($request->input('date_from'), $request->input('date_to'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'calibration_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $calibrations = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $calibrations->items(),
            'pagination' => [
                'current_page' => $calibrations->currentPage(),
                'last_page' => $calibrations->lastPage(),
                'per_page' => $calibrations->perPage(),
                'total' => $calibrations->total(),
            ],
        ]);
    }

    /**
     * Create sensor calibration.
     */
    public function createCalibration(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sensor_id' => 'required|uuid|exists:sensors,id',
            'calibration_type' => 'required|in:routine,initial,repair,verification,field,laboratory,certification',
            'reference_value' => 'nullable|numeric',
            'measured_value' => 'nullable|numeric',
            'correction_factor' => 'nullable|numeric',
            'offset' => 'nullable|numeric',
            'linearity_error' => 'nullable|numeric',
            'hysteresis_error' => 'nullable|numeric',
            'repeatability_error' => 'nullable|numeric',
            'temperature_coefficient' => 'nullable|numeric',
            'humidity_coefficient' => 'nullable|numeric',
            'calibration_certificate' => 'nullable|string|max:500',
            'equipment_used' => 'nullable|string|max:500',
            'environment_conditions' => 'nullable|array',
            'notes' => 'nullable|string',
            'next_calibration_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['calibration_date'] = now();
        $validated['performed_by'] = auth()->id();

        DB::beginTransaction();
        try {
            $calibration = SensorCalibration::create($validated);

            // Update sensor calibration dates
            $sensor = $calibration->sensor;
            $sensor->update([
                'calibration_date' => now(),
                'next_calibration_date' => $validated['next_calibration_date'] ?? now()->addMonths(6),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Calibration created successfully',
                'data' => $calibration->load(['sensor.asset', 'sensor.sensorType', 'performer']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create calibration',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Approve calibration.
     */
    public function approveCalibration(Request $request, SensorCalibration $calibration): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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

        $calibration->approve(auth()->user(), $validated['notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Calibration approved successfully',
            'data' => $calibration->fresh()->load(['sensor.asset', 'approver']),
        ]);
    }

    /**
     * Reject calibration.
     */
    public function rejectCalibration(Request $request, SensorCalibration $calibration): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $calibration->reject(auth()->user(), $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Calibration rejected successfully',
            'data' => $calibration->fresh()->load(['sensor.asset', 'approver']),
        ]);
    }

    /**
     * Get IoT statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'sensors' => [
                'total' => Sensor::count(),
                'active' => Sensor::active()->count(),
                'inactive' => Sensor::where('status', SensorStatus::INACTIVE)->count(),
                'offline' => Sensor::offline()->count(),
                'needs_calibration' => Sensor::needsCalibration()->count(),
                'low_battery' => Sensor::lowBattery()->count(),
                'poor_signal' => Sensor::poorSignal()->count(),
            ],
            'readings' => [
                'total' => SensorReading::count(),
                'today' => SensorReading::whereDate('timestamp', today())->count(),
                'this_week' => SensorReading::whereBetween('timestamp', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'this_month' => SensorReading::whereMonth('timestamp', now()->month)
                    ->whereYear('timestamp', now()->year)
                    ->count(),
                'with_errors' => SensorReading::withErrors()->count(),
                'poor_quality' => SensorReading::poorQuality()->count(),
            ],
            'alerts' => [
                'total' => SensorAlert::count(),
                'active' => SensorAlert::unacknowledged()->unresolved()->count(),
                'unacknowledged' => SensorAlert::unacknowledged()->count(),
                'unresolved' => SensorAlert::unresolved()->count(),
                'critical' => SensorAlert::critical()->count(),
                'high' => SensorAlert::where('severity', AlertSeverity::HIGH)->count(),
                'by_type' => SensorAlert::select('alert_type', DB::raw('count(*) as count'))
                    ->groupBy('alert_type')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->alert_type => $item->count];
                    }),
                'by_severity' => SensorAlert::select('severity', DB::raw('count(*) as count'))
                    ->groupBy('severity')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->severity => $item->count];
                    }),
            ],
            'calibrations' => [
                'total' => SensorCalibration::count(),
                'pending' => SensorCalibration::pending()->count(),
                'approved' => SensorCalibration::approved()->count(),
                'failed' => SensorCalibration::where('calibration_status', CalibrationStatus::FAILED)->count(),
                'this_month' => SensorCalibration::whereMonth('calibration_date', now()->month)
                    ->whereYear('calibration_date', now()->year)
                    ->count(),
                'overdue' => Sensor::needsCalibration()->count(),
            ],
            'sensor_types' => SensorType::active()->withCount('sensors')->get(),
            'recent_activity' => [
                'recent_readings' => SensorReading::with(['sensor.asset'])
                    ->latest('timestamp')
                    ->limit(10)
                    ->get(),
                'recent_alerts' => SensorAlert::with(['sensor.asset'])
                    ->latest('triggered_at')
                    ->limit(10)
                    ->get(),
                'recent_calibrations' => SensorCalibration::with(['sensor.asset', 'performer'])
                    ->latest('calibration_date')
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
     * Get sensor data analytics.
     */
    public function analytics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sensor_id' => 'required|uuid|exists:sensors,id',
            'period' => 'required|in:hour,day,week,month,year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $sensor = Sensor::findOrFail($validated['sensor_id']);

        $analytics = $this->iotService->generateSensorAnalytics($sensor, $validated['period'], [
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get sensor health report.
     */
    public function healthReport(): JsonResponse
    {
        $report = $this->iotService->generateHealthReport();

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Process sensor data from IoT device.
     */
    public function processSensorData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sensor_id' => 'required|uuid|exists:sensors,id',
            'readings' => 'required|array|min:1',
            'readings.*.value' => 'required|numeric',
            'readings.*.timestamp' => 'required|date',
            'readings.*.quality' => 'nullable|numeric|min:0|max:1',
            'readings.*.battery_level' => 'nullable|integer|min:0|max:100',
            'readings.*.signal_strength' => 'nullable|integer|min:0|max:100',
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

        try {
            $results = $this->iotService->processBatchReadings($validated['sensor_id'], $validated['readings'], $validated['metadata'] ?? []);

            return response()->json([
                'success' => true,
                'message' => 'Sensor data processed successfully',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process sensor data',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }
}
