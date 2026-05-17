<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\PredictiveModel;
use App\Models\Prediction;
use App\Models\MaintenanceRecommendation;
use App\Models\ModelTrainingHistory;
use App\Models\ModelPerformance;
use App\Services\PredictiveMaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PredictiveMaintenanceController extends Controller
{
    protected PredictiveMaintenanceService $predictiveService;

    public function __construct(PredictiveMaintenanceService $predictiveService)
    {
        $this->predictiveService = $predictiveService;
    }

    /**
     * Display a listing of predictive models.
     */
    public function models(Request $request): JsonResponse
    {
        $query = PredictiveModel::with(['creator', 'trainingHistories', 'performances']);

        // Apply filters
        if ($request->has('model_type')) {
            $query->byType($request->input('model_type'));
        }

        if ($request->has('algorithm')) {
            $query->byAlgorithm($request->input('algorithm'));
        }

        if ($request->boolean('active_only', false)) {
            $query->active();
        }

        if ($request->boolean('needs_retraining', false)) {
            $query->needsRetraining();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $models = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $models->items(),
            'pagination' => [
                'current_page' => $models->currentPage(),
                'last_page' => $models->lastPage(),
                'per_page' => $models->perPage(),
                'total' => $models->total(),
                'from' => $models->firstItem(),
                'to' => $models->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created predictive model in storage.
     */
    public function storeModel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'model_type' => 'required|in:failure_prediction,remaining_useful_life,anomaly_detection,predictive_maintenance,condition_monitoring,energy_consumption,performance_degradation,optimal_maintenance',
            'algorithm' => 'required|in:random_forest,gradient_boosting,linear_regression,logistic_regression,support_vector_machine,neural_network,lstm,arima,isolation_forest,one_class_svm,k_means,dbscan,decision_tree,xgboost,lightgbm',
            'target_variable' => 'required|string',
            'input_features' => 'required|array|min:1',
            'hyperparameters' => 'nullable|array',
            'training_data_period' => 'nullable|array',
            'validation_data_period' => 'nullable|array',
            'auto_retrain' => 'boolean',
            'retrain_frequency_days' => 'nullable|integer|min:1',
            'min_accuracy_threshold' => 'nullable|numeric|min:0|max:1',
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
        $validated['is_active'] = true;
        $validated['auto_retrain'] = $validated['auto_retrain'] ?? false;
        $validated['retrain_frequency_days'] = $validated['retrain_frequency_days'] ?? 30;
        $validated['min_accuracy_threshold'] = $validated['min_accuracy_threshold'] ?? 0.7;
        $validated['model_version'] = 'v1.0.0';

        DB::beginTransaction();
        try {
            $model = PredictiveModel::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Predictive model created successfully',
                'data' => $model->load(['creator']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create predictive model',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Display the specified predictive model.
     */
    public function showModel(PredictiveModel $model): JsonResponse
    {
        $model->load([
            'creator',
            'trainingHistories' => function ($query) {
                $query->latest('training_started_at');
            },
            'performances' => function ($query) {
                $query->latest('evaluation_date');
            },
            'predictions' => function ($query) {
                $query->latest('prediction_date')->limit(10);
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => $model,
        ]);
    }

    /**
     * Update the specified predictive model in storage.
     */
    public function updateModel(Request $request, PredictiveModel $model): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'input_features' => 'sometimes|required|array|min:1',
            'hyperparameters' => 'sometimes|nullable|array',
            'auto_retrain' => 'sometimes|boolean',
            'retrain_frequency_days' => 'sometimes|nullable|integer|min:1',
            'min_accuracy_threshold' => 'sometimes|nullable|numeric|min:0|max:1',
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

        $model->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Predictive model updated successfully',
            'data' => $model->fresh()->load(['creator']),
        ]);
    }

    /**
     * Remove the specified predictive model from storage.
     */
    public function destroyModel(PredictiveModel $model): JsonResponse
    {
        // Check if model has predictions
        if ($model->predictions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete model with predictions',
            ], 422);
        }

        $model->delete();

        return response()->json([
            'success' => true,
            'message' => 'Predictive model deleted successfully',
        ]);
    }

    /**
     * Train a predictive model.
     */
    public function trainModel(Request $request, PredictiveModel $model): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'training_data' => 'required|array|min:10',
            'training_data.*.features' => 'required|array',
            'training_data.*.target' => 'required|numeric',
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
            $result = $this->predictiveService->trainModel($model, $validated['training_data']);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Model training completed successfully',
                    'data' => $result,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Model training failed',
                    'error' => $result['error'],
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Training failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Generate predictions for assets.
     */
    public function generatePredictions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model_id' => 'required|uuid|exists:predictive_models,id',
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'uuid|exists:assets,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $model = PredictiveModel::findOrFail($validated['model_id']);
        $assets = Asset::whereIn('id', $validated['asset_ids'])->get();

        try {
            $result = $this->predictiveService->generatePredictions($model, $assets->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Predictions generated successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prediction generation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Display a listing of predictions.
     */
    public function predictions(Request $request): JsonResponse
    {
        $query = Prediction::with(['predictiveModel', 'asset', 'maintenanceRecommendations']);

        // Apply filters
        if ($request->has('model_id')) {
            $query->where('predictive_model_id', $request->input('model_id'));
        }

        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->input('asset_id'));
        }

        if ($request->has('prediction_type')) {
            $query->byType($request->input('prediction_type'));
        }

        if ($request->has('risk_level')) {
            $query->byRiskLevel($request->input('risk_level'));
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'needs_validation') {
                $query->needsValidation();
            } elseif ($status === 'validated') {
                $query->validated();
            } elseif ($status === 'high_risk') {
                $query->highRisk();
            }
        }

        // Date range filter
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->targetDateRange(
                Carbon::parse($request->input('date_from')),
                Carbon::parse($request->input('date_to'))
            );
        }

        // Sort
        $sortBy = $request->input('sort_by', 'prediction_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $predictions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $predictions->items(),
            'pagination' => [
                'current_page' => $predictions->currentPage(),
                'last_page' => $predictions->lastPage(),
                'per_page' => $predictions->perPage(),
                'total' => $predictions->total(),
                'from' => $predictions->firstItem(),
                'to' => $predictions->lastItem(),
            ],
        ]);
    }

    /**
     * Display the specified prediction.
     */
    public function showPrediction(Prediction $prediction): JsonResponse
    {
        $prediction->load([
            'predictiveModel',
            'asset',
            'maintenanceRecommendations',
        ]);

        return response()->json([
            'success' => true,
            'data' => $prediction,
        ]);
    }

    /**
     * Validate prediction with actual value.
     */
    public function validatePrediction(Request $request, Prediction $prediction): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'actual_value' => 'required|numeric',
            'actual_date' => 'nullable|date',
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
            $prediction->validate(
                $validated['actual_value'],
                $validated['actual_date'] ? Carbon::parse($validated['actual_date']) : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Prediction validated successfully',
                'data' => $prediction->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Display a listing of maintenance recommendations.
     */
    public function recommendations(Request $request): JsonResponse
    {
        $query = MaintenanceRecommendation::with([
            'prediction.predictiveModel',
            'asset',
            'assignedTo',
            'approver',
            'workOrder'
        ]);

        // Apply filters
        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->input('asset_id'));
        }

        if ($request->has('recommendation_type')) {
            $query->byType($request->input('recommendation_type'));
        }

        if ($request->has('urgency')) {
            $query->byUrgency($request->input('urgency'));
        }

        if ($request->has('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->boolean('pending', false)) {
            $query->pending();
        }

        if ($request->boolean('overdue', false)) {
            $query->overdue();
        }

        if ($request->boolean('urgent', false)) {
            $query->urgent();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $recommendations = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $recommendations->items(),
            'pagination' => [
                'current_page' => $recommendations->currentPage(),
                'last_page' => $recommendations->lastPage(),
                'per_page' => $recommendations->perPage(),
                'total' => $recommendations->total(),
                'from' => $recommendations->firstItem(),
                'to' => $recommendations->lastItem(),
            ],
        ]);
    }

    /**
     * Display the specified maintenance recommendation.
     */
    public function showRecommendation(MaintenanceRecommendation $recommendation): JsonResponse
    {
        $recommendation->load([
            'prediction.predictiveModel',
            'asset',
            'assignedTo',
            'approver',
            'workOrder',
        ]);

        return response()->json([
            'success' => true,
            'data' => $recommendation,
        ]);
    }

    /**
     * Approve maintenance recommendation.
     */
    public function approveRecommendation(Request $request, MaintenanceRecommendation $recommendation): JsonResponse
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

        try {
            $recommendation->approve(auth()->user(), $validated['notes'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Recommendation approved successfully',
                'data' => $recommendation->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Approval failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Reject maintenance recommendation.
     */
    public function rejectRecommendation(Request $request, MaintenanceRecommendation $recommendation): JsonResponse
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

        try {
            $recommendation->reject(auth()->user(), $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Recommendation rejected successfully',
                'data' => $recommendation->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rejection failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Complete maintenance recommendation.
     */
    public function completeRecommendation(Request $request, MaintenanceRecommendation $recommendation): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'actual_cost' => 'nullable|numeric|min:0',
            'actual_duration_hours' => 'nullable|numeric|min:0',
            'effectiveness_rating' => 'nullable|numeric|min:1|max:5',
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
            $recommendation->complete(auth()->user(), $validated);

            return response()->json([
                'success' => true,
                'message' => 'Recommendation completed successfully',
                'data' => $recommendation->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Completion failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Create work order from recommendation.
     */
    public function createWorkOrder(MaintenanceRecommendation $recommendation): JsonResponse
    {
        try {
            $workOrder = $recommendation->createWorkOrder(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Work order created successfully',
                'data' => $workOrder->load(['asset', 'assignedTo']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Work order creation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Display model training histories.
     */
    public function trainingHistories(Request $request): JsonResponse
    {
        $query = ModelTrainingHistory::with(['predictiveModel', 'creator']);

        // Apply filters
        if ($request->has('model_id')) {
            $query->where('predictive_model_id', $request->input('model_id'));
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'successful') {
                $query->successful();
            } elseif ($status === 'failed') {
                $query->failed();
            } elseif ($status === 'in_progress') {
                $query->inProgress();
            }
        }

        // Sort
        $sortBy = $request->input('sort_by', 'training_started_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $histories = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $histories->items(),
            'pagination' => [
                'current_page' => $histories->currentPage(),
                'last_page' => $histories->lastPage(),
                'per_page' => $histories->perPage(),
                'total' => $histories->total(),
                'from' => $histories->firstItem(),
                'to' => $histories->lastItem(),
            ],
        ]);
    }

    /**
     * Display model performances.
     */
    public function modelPerformances(Request $request): JsonResponse
    {
        $query = ModelPerformance::with(['predictiveModel', 'creator']);

        // Apply filters
        if ($request->has('model_id')) {
            $query->where('predictive_model_id', $request->input('model_id'));
        }

        if ($request->has('evaluation_type')) {
            $query->byType($request->input('evaluation_type'));
        }

        if ($request->has('dataset_type')) {
            $query->byDatasetType($request->input('dataset_type'));
        }

        if ($request->boolean('with_drift', false)) {
            $query->withDrift();
        }

        if ($request->boolean('recent', false)) {
            $query->recent(30);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'evaluation_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $performances = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $performances->items(),
            'pagination' => [
                'current_page' => $performances->currentPage(),
                'last_page' => $performances->lastPage(),
                'per_page' => $performances->perPage(),
                'total' => $performances->total(),
                'from' => $performances->firstItem(),
                'to' => $performances->lastItem(),
            ],
        ]);
    }

    /**
     * Evaluate model performance.
     */
    public function evaluateModel(Request $request, PredictiveModel $model): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'test_data' => 'required|array|min:10',
            'test_data.*.features' => 'required|array',
            'test_data.*.target' => 'required|numeric',
            'evaluation_type' => 'nullable|in:validation,testing,cross_validation,production,drift_detection,benchmark,a_b_testing',
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
            $performance = $this->predictiveService->evaluateModel(
                $model,
                $validated['test_data'],
                $validated['evaluation_type'] ?? 'testing'
            );

            return response()->json([
                'success' => true,
                'message' => 'Model evaluation completed successfully',
                'data' => $performance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Model evaluation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Detect model drift.
     */
    public function detectModelDrift(PredictiveModel $model): JsonResponse
    {
        try {
            $driftAnalysis = $this->predictiveService->detectModelDrift($model);

            return response()->json([
                'success' => true,
                'message' => 'Drift detection completed',
                'data' => $driftAnalysis,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Drift detection failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Generate predictive maintenance schedule.
     */
    public function generateSchedule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prediction_ids' => 'required|array|min:1',
            'prediction_ids.*' => 'uuid|exists:predictions,id',
            'optimization_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $predictions = Prediction::whereIn('id', $validated['prediction_ids'])->get();

        try {
            $schedule = $this->predictiveService->generateMaintenanceSchedule($predictions->toArray());

            if ($validated['optimization_enabled'] ?? false) {
                $schedule = $this->predictiveService->optimizeMaintenanceSchedule($schedule);
            }

            return response()->json([
                'success' => true,
                'message' => 'Maintenance schedule generated successfully',
                'data' => $schedule,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule generation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Get predictive maintenance statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'models' => [
                'total' => PredictiveModel::count(),
                'active' => PredictiveModel::active()->count(),
                'needs_retraining' => PredictiveModel::needsRetraining()->count(),
                'by_type' => PredictiveModel::select('model_type', DB::raw('count(*) as count'))
                    ->groupBy('model_type')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->model_type => $item->count];
                    }),
                'by_algorithm' => PredictiveModel::select('algorithm', DB::raw('count(*) as count'))
                    ->groupBy('algorithm')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->algorithm => $item->count];
                    }),
            ],
            'predictions' => [
                'total' => Prediction::count(),
                'today' => Prediction::whereDate('prediction_date', today())->count(),
                'this_week' => Prediction::whereBetween('prediction_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'this_month' => Prediction::whereMonth('prediction_date', now()->month)
                    ->whereYear('prediction_date', now()->year)
                    ->count(),
                'high_risk' => Prediction::highRisk()->count(),
                'validated' => Prediction::validated()->count(),
                'needs_validation' => Prediction::needsValidation()->count(),
                'by_type' => Prediction::select('prediction_type', DB::raw('count(*) as count'))
                    ->groupBy('prediction_type')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->prediction_type => $item->count];
                    }),
                'by_risk_level' => Prediction::select('risk_level', DB::raw('count(*) as count'))
                    ->groupBy('risk_level')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->risk_level => $item->count];
                    }),
            ],
            'recommendations' => [
                'total' => MaintenanceRecommendation::count(),
                'pending' => MaintenanceRecommendation::pending()->count(),
                'approved' => MaintenanceRecommendation::approved()->count(),
                'rejected' => MaintenanceRecommendation::rejected()->count(),
                'completed' => MaintenanceRecommendation::completed()->count(),
                'overdue' => MaintenanceRecommendation::overdue()->count(),
                'urgent' => MaintenanceRecommendation::urgent()->count(),
                'by_type' => MaintenanceRecommendation::select('recommendation_type', DB::raw('count(*) as count'))
                    ->groupBy('recommendation_type')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->recommendation_type => $item->count];
                    }),
                'by_urgency' => MaintenanceRecommendation::select('urgency', DB::raw('count(*) as count'))
                    ->groupBy('urgency')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->urgency => $item->count];
                    }),
                'estimated_total_cost' => MaintenanceRecommendation::sum('estimated_cost'),
            ],
            'training' => [
                'total_trainings' => ModelTrainingHistory::count(),
                'successful' => ModelTrainingHistory::successful()->count(),
                'failed' => ModelTrainingHistory::failed()->count(),
                'in_progress' => ModelTrainingHistory::inProgress()->count(),
                'this_month' => ModelTrainingHistory::whereMonth('training_started_at', now()->month)
                    ->whereYear('training_started_at', now()->year)
                    ->count(),
                'average_duration' => ModelTrainingHistory::successful()
                    ->avg('training_duration_seconds'),
            ],
            'performance' => [
                'total_evaluations' => ModelPerformance::count(),
                'with_drift' => ModelPerformance::withDrift()->count(),
                'recent_evaluations' => ModelPerformance::recent(30)->count(),
                'average_accuracy' => ModelPerformance::avg('accuracy_score'),
                'average_f1_score' => ModelPerformance::avg('f1_score'),
            ],
            'recent_activity' => [
                'recent_predictions' => Prediction::with(['asset', 'predictiveModel'])
                    ->latest('prediction_date')
                    ->limit(10)
                    ->get(),
                'recent_recommendations' => MaintenanceRecommendation::with(['asset'])
                    ->latest('created_at')
                    ->limit(10)
                    ->get(),
                'recent_trainings' => ModelTrainingHistory::with(['predictiveModel'])
                    ->latest('training_started_at')
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
     * Get predictive maintenance dashboard data.
     */
    public function dashboard(): JsonResponse
    {
        $dashboard = [
            'overview' => [
                'active_models' => PredictiveModel::active()->count(),
                'total_predictions_today' => Prediction::whereDate('prediction_date', today())->count(),
                'high_risk_predictions' => Prediction::highRisk()->count(),
                'pending_recommendations' => MaintenanceRecommendation::pending()->count(),
            ],
            'model_performance' => [
                'top_performing_models' => PredictiveModel::active()
                    ->with(['performances' => function ($query) {
                        $query->latest('evaluation_date')->limit(1);
                    }])
                    ->get()
                    ->map(function ($model) {
                        $performance = $model->performances->first();
                        return [
                            'id' => $model->id,
                            'name' => $model->name,
                            'accuracy' => $performance ? $performance->accuracy_score : 0,
                            'f1_score' => $performance ? $performance->f1_score : 0,
                            'last_trained' => $model->last_trained_at?->toISOString(),
                        ];
                    })
                    ->sortByDesc('accuracy')
                    ->take(5),
                'models_needing_retraining' => PredictiveModel::needsRetraining()
                    ->with(['creator'])
                    ->get()
                    ->map(function ($model) {
                        return [
                            'id' => $model->id,
                            'name' => $model->name,
                            'accuracy' => $model->accuracy_score,
                            'last_trained' => $model->last_trained_at?->toISOString(),
                            'next_retrain' => $model->next_retrain_at?->toISOString(),
                        ];
                    }),
            ],
            'risk_analysis' => [
                'risk_distribution' => Prediction::select('risk_level', DB::raw('count(*) as count'))
                    ->groupBy('risk_level')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->risk_level => $item->count];
                    }),
                'high_risk_assets' => Prediction::highRisk()
                    ->with(['asset'])
                    ->get()
                    ->map(function ($prediction) {
                        return [
                            'asset_id' => $prediction->asset_id,
                            'asset_name' => $prediction->asset->name,
                            'risk_level' => $prediction->risk_level->getDisplayName(),
                            'prediction_value' => $prediction->predicted_value,
                            'target_date' => $prediction->target_date->format('Y-m-d'),
                        ];
                    })
                    ->take(10),
            ],
            'recommendations_summary' => [
                'by_status' => MaintenanceRecommendation::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->status => $item->count];
                    }),
                'urgent_recommendations' => MaintenanceRecommendation::urgent()
                    ->with(['asset'])
                    ->get()
                    ->map(function ($rec) {
                        return [
                            'id' => $rec->id,
                            'asset_name' => $rec->asset->name,
                            'type' => $rec->recommendation_type->getDisplayName(),
                            'urgency' => $rec->urgency->getDisplayName(),
                            'deadline' => $rec->deadline_date?->format('Y-m-d'),
                            'days_overdue' => $rec->isOverdue() ? $rec->days_until_deadline : null,
                        ];
                    })
                    ->take(10),
            ],
            'trends' => [
                'prediction_trends' => $this->getPredictionTrends(),
                'recommendation_trends' => $this->getRecommendationTrends(),
                'model_accuracy_trends' => $this->getModelAccuracyTrends(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }

    /**
     * Get prediction trends.
     */
    private function getPredictionTrends(): array
    {
        $trends = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'predictions' => Prediction::whereDate('prediction_date', $date)->count(),
                'high_risk' => Prediction::whereDate('prediction_date', $date)->highRisk()->count(),
            ];
        }
        
        return $trends;
    }

    /**
     * Get recommendation trends.
     */
    private function getRecommendationTrends(): array
    {
        $trends = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'created' => MaintenanceRecommendation::whereDate('created_at', $date)->count(),
                'completed' => MaintenanceRecommendation::whereDate('completed_at', $date)->count(),
            ];
        }
        
        return $trends;
    }

    /**
     * Get model accuracy trends.
     */
    private function getModelAccuracyTrends(): array
    {
        $trends = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $performances = ModelPerformance::whereMonth('evaluation_date', $date->month)
                ->whereYear('evaluation_date', $date->year)
                ->get();
            
            $trends[] = [
                'date' => $date->format('Y-m'),
                'average_accuracy' => $performances->avg('accuracy_score') ?? 0,
                'evaluation_count' => $performances->count(),
            ];
        }
        
        return $trends;
    }
}
