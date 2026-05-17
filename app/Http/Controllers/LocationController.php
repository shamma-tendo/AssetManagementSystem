<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /**
     * Display a listing of the locations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Location::with(['parent', 'children']);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $locations = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $locations->items(),
            'pagination' => [
                'current_page' => $locations->currentPage(),
                'last_page' => $locations->lastPage(),
                'per_page' => $locations->perPage(),
                'total' => $locations->total(),
                'from' => $locations->firstItem(),
                'to' => $locations->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created location in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:locations,code',
            'parent_location_id' => 'nullable|uuid|exists:locations,id',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $location = Location::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Location created successfully',
            'data' => $location->load(['parent', 'children']),
        ], 201);
    }

    /**
     * Display the specified location.
     */
    public function show(Location $location): JsonResponse
    {
        $location->load(['parent', 'children', 'assets']);

        return response()->json([
            'success' => true,
            'data' => $location,
        ]);
    }

    /**
     * Update the specified location in storage.
     */
    public function update(Request $request, Location $location): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('locations', 'code')->ignore($location->id)],
            'parent_location_id' => 'sometimes|nullable|uuid|exists:locations,id',
            'address' => 'sometimes|nullable|string|max:500',
            'city' => 'sometimes|nullable|string|max:100',
            'state' => 'sometimes|nullable|string|max:100',
            'postal_code' => 'sometimes|nullable|string|max:20',
            'country' => 'sometimes|nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $location->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => $location->load(['parent', 'children']),
        ]);
    }

    /**
     * Remove the specified location from storage.
     */
    public function destroy(Location $location): JsonResponse
    {
        // Check if location has associated assets or child locations
        if ($location->assets()->exists() || $location->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete location with associated assets or child locations',
            ], 422);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Location deleted successfully',
        ]);
    }

    /**
     * Get active locations.
     */
    public function active(): JsonResponse
    {
        $locations = Location::active()
            ->with(['parent', 'children'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locations,
        ]);
    }

    /**
     * Get root locations (locations without parent).
     */
    public function root(): JsonResponse
    {
        $locations = Location::root()
            ->with(['children'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locations,
        ]);
    }
}
