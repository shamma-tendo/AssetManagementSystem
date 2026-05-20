<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\MaintenanceSchedule;
use App\Models\InsurancePolicy;
use App\Models\AssetLoan;
use App\Models\AssetDocument;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Household Dashboard Controller
 * 
 * For individual/household users - provides:
 * - Personal asset inventory
 * - Insurance policy tracking
 * - Warranty management
 * - Maintenance reminders
 * - Loan/rental history
 */
class HouseholdDashboardController extends Controller
{
    /**
     * Show the household dashboard
     */
    public function index()
    {
        $user = auth()->user();
        $organization = $user->organization;

        // Redirect to appropriate dashboard if not household
        if (!$organization || !$organization->isHousehold()) {
            return redirect()->route($user->getDashboardRoute());
        }

        // All personal assets
        $assets = Asset::where('organization_id', $organization->id)
            ->with('category', 'location')
            ->paginate(15);

        // For print: all assets unpaginated
        $allAssets = Asset::where('organization_id', $organization->id)
            ->with('category', 'location')
            ->orderBy('name')
            ->get();

        // Assets statistics
        $assetStats = [
            'total' => Asset::where('organization_id', $organization->id)->count(),
            'valuable' => Asset::where('organization_id', $organization->id)
                ->where('estimated_value', '>', 1000)
                ->count(),
            'with_warranty' => Asset::where('organization_id', $organization->id)
                ->whereHas('warranties')
                ->count(),
            'with_insurance' => Asset::where('organization_id', $organization->id)
                ->whereHas('insurancePolicies')
                ->count(),
        ];

        // Insurance policies
        $insurancePolicies = InsurancePolicy::where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderBy('end_date')
            ->limit(10)
            ->get();

        // Warranties - expiring soon
        $expiringWarranties = AssetWarranty::query()
            ->where('organization_id', $organization->id)
            ->where('warranty_end_date', '>', now())
            ->where('warranty_end_date', '<=', now()->addDays(30))
            ->with('asset')
            ->orderBy('warranty_end_date')
            ->get();

        // Maintenance schedules
        $upcomingMaintenance = MaintenanceSchedule::query()
            ->where('organization_id', $organization->id)
            ->where('next_service_date', '>', now())
            ->where('next_service_date', '<=', now()->addDays(60))
            ->with('asset')
            ->orderBy('next_service_date')
            ->limit(10)
            ->get();

        // Loans - active
        $activeLoans = AssetLoan::where('organization_id', $organization->id)
            ->where('status', 'active')
            ->with('asset')
            ->get();

        // Portfolio value
        $totalValue = Asset::where('organization_id', $organization->id)
            ->sum('estimated_value');

        return view('dashboards.household', [
            'organization' => $organization,
            'assets' => $assets,
            'allAssets' => $allAssets,
            'assetStats' => $assetStats,
            'insurancePolicies' => $insurancePolicies,
            'expiringWarranties' => $expiringWarranties,
            'upcomingMaintenance' => $upcomingMaintenance,
            'activeLoans' => $activeLoans,
            'totalValue' => $totalValue,
        ]);
    }

    /**
     * Store a new personal asset
     */
    public function storeAsset(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization || !$organization->isHousehold()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $request->validate([
            'name'            => 'required|string|max:255',
            'category_id'     => 'nullable|uuid|exists:categories,id',
            'location_id'     => 'nullable|string|max:255',
            'estimated_value' => 'nullable|numeric|min:0',
            'purchase_date'   => 'nullable|date',
            'serial_number'   => 'nullable|string|max:255',
            'manufacturer'    => 'nullable|string|max:255',
            'description'     => 'nullable|string|max:2000',
        ]);

        // Resolve location: UUID → use directly; district name → find or create
        $locationId = $request->location_id;
        if ($locationId && !\Illuminate\Support\Str::isUuid($locationId)) {
            $name = trim($locationId);
            $loc  = \App\Models\Location::whereRaw('LOWER(name) = ?', [strtolower($name)])->first()
                 ?? \App\Models\Location::create(['name' => $name, 'address' => $name]);
            $locationId = $loc->id;
        }

        Asset::create([
            'name'            => $request->name,
            'serial_number'   => $request->serial_number ?? 'HH-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'category_id'     => $request->category_id,
            'location_id'     => $locationId,
            'estimated_value' => $request->estimated_value,
            'purchase_cost'   => $request->estimated_value ?? 0,
            'current_value'   => $request->estimated_value ?? 0,
            'purchase_date'   => $request->purchase_date,
            'manufacturer'    => $request->manufacturer,
            'description'     => $request->description,
            'organization_id' => $organization->id,
            'status'          => 'Active',
            'created_by'      => $user->id,
        ]);

        return redirect()->route('household.dashboard')
            ->with('success', 'Asset added successfully!');
    }

    /**
     * Add a new personal asset (form)
     */
    public function createAsset()
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization || !$organization->isHousehold()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $categories = \App\Models\Category::all();
        $locations = \App\Models\Location::where('organization_id', $organization->id)
            ->orWhereNull('organization_id')
            ->get();

        return view('dashboards.household-asset-create', [
            'categories' => $categories,
            'locations' => $locations,
        ]);
    }

    /**
     * View asset details
     */
    public function viewAsset($assetId)
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization || !$organization->isHousehold()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $asset = Asset::where('organization_id', $organization->id)
            ->with('category', 'location', 'warranties', 'insurancePolicies', 'documents')
            ->findOrFail($assetId);

        $warranties = $asset->warranties;
        $insurancePolicies = $asset->insurancePolicies;
        $documents = $asset->documents;
        $maintenanceRecords = \App\Models\MaintenanceRecord::where('asset_id', $assetId)
            ->orderBy('date', 'desc')
            ->get();

        return view('dashboards.household-asset-detail', [
            'asset' => $asset,
            'warranties' => $warranties,
            'insurancePolicies' => $insurancePolicies,
            'documents' => $documents,
            'maintenanceRecords' => $maintenanceRecords,
        ]);
    }

    /**
     * Maintenance Reminders page
     */
    public function reminders()
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization || !$organization->isHousehold()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $reminders = MaintenanceSchedule::where('organization_id', $organization->id)
            ->with('asset')
            ->orderBy('next_service_date')
            ->get();

        $assets = Asset::where('organization_id', $organization->id)
            ->orderBy('name')
            ->get();

        return view('dashboards.household-reminders', compact('reminders', 'assets'));
    }

    /**
     * Store a new maintenance reminder
     */
    public function storeReminder(Request $request)
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization || !$organization->isHousehold()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $request->validate([
            'asset_id'             => 'required|uuid|exists:assets,id',
            'service_type'         => 'required|string|max:255',
            'next_service_date'    => 'required|date|after:today',
            'service_interval_days'=> 'nullable|integer|min:1',
            'service_provider'     => 'nullable|string|max:255',
            'estimated_cost'       => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string|max:1000',
        ]);

        MaintenanceSchedule::create([
            'organization_id'      => $organization->id,
            'asset_id'             => $request->asset_id,
            'service_type'         => $request->service_type,
            'next_service_date'    => $request->next_service_date,
            'service_interval_days'=> $request->service_interval_days,
            'service_provider'     => $request->service_provider,
            'estimated_cost'       => $request->estimated_cost,
            'notes'                => $request->notes,
        ]);

        return redirect()->route('household.reminders')->with('success', 'Reminder added!');
    }

    /**
     * Delete a maintenance reminder
     */
    public function deleteReminder(MaintenanceSchedule $reminder)
    {
        $user = auth()->user();
        if ($reminder->organization_id !== $user->organization_id) abort(403);
        $reminder->delete();
        return redirect()->route('household.reminders')->with('success', 'Reminder deleted.');
    }

    /**
     * Photo Storage page
     */
    public function photos()
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization || !$organization->isHousehold()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $photos = AssetDocument::where('organization_id', $organization->id)
            ->where('document_type', 'photo')
            ->with('asset')
            ->orderByDesc('created_at')
            ->get();

        $assets = Asset::where('organization_id', $organization->id)
            ->orderBy('name')
            ->get();

        return view('dashboards.household-photos', compact('photos', 'assets'));
    }

    /**
     * Upload a photo
     */
    public function storePhoto(Request $request)
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization || !$organization->isHousehold()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $request->validate([
            'asset_id' => 'required|uuid|exists:assets,id',
            'photo'    => 'required|image|max:5120',
            'notes'    => 'nullable|string|max:500',
        ]);

        $file = $request->file('photo');
        $path = $file->store('household/photos/' . $organization->id, 'public');

        AssetDocument::create([
            'organization_id' => $organization->id,
            'asset_id'        => $request->asset_id,
            'document_type'   => 'photo',
            'file_path'       => $path,
            'file_name'       => $file->getClientOriginalName(),
            'file_size'       => $file->getSize(),
            'mime_type'       => $file->getMimeType(),
            'notes'           => $request->notes,
            'uploaded_by'     => $user->id,
        ]);

        return redirect()->route('household.photos')->with('success', 'Photo uploaded!');
    }

    /**
     * Delete a photo
     */
    public function deletePhoto(AssetDocument $document)
    {
        $user = auth()->user();
        if ($document->organization_id !== $user->organization_id) abort(403);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return redirect()->route('household.photos')->with('success', 'Photo deleted.');
    }

    /**
     * Insurance & warranty management
     */
    public function insurance()
    {
        $user = auth()->user();
        $organization = $user->organization;

        if (!$organization || !$organization->isHousehold()) {
            return redirect()->route($user->getDashboardRoute());
        }

        $insurancePolicies = InsurancePolicy::where('organization_id', $organization->id)
            ->with('asset')
            ->orderBy('end_date')
            ->paginate(15);

        return view('dashboards.household-insurance', [
            'insurancePolicies' => $insurancePolicies,
        ]);
    }
}
