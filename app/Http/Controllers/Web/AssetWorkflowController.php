<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetConditionReport;
use App\Models\AssetRequest;
use App\Models\Category;
use App\Models\LeaveRequest;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Asset Workflow Controller
 *
 * CEO side:
 *  - Add assets to inventory with full properties
 *  - View inventory
 *  - View staff asset requests and approve/reject + assign
 *  - View condition reports (repair needed / stolen)
 *
 * Staff side:
 *  - Request an asset (purpose, quantity, location)
 *  - View request history
 */
class AssetWorkflowController extends Controller
{
    // ─────────────────────────────────────────────────────
    // CEO: Inventory Management
    // ─────────────────────────────────────────────────────

    public function ceoInventory()
    {
        $user = auth()->user();
        if (!$user->isExecutive()) return redirect()->route($user->getDashboardRoute());

        $org = $user->organization;

        $assets = Asset::where('organization_id', $org->id)
            ->with('category', 'location')
            ->orderByDesc('created_at')
            ->paginate(20);

        $categories = Category::orderBy('name')->get();
        $locations  = Location::orderBy('name')->get();

        return view('workflow.ceo-inventory', compact('assets', 'categories', 'locations', 'org'));
    }

    public function ceoStoreAsset(Request $request)
    {
        $user = auth()->user();
        if (!$user->isExecutive()) return redirect()->route($user->getDashboardRoute());

        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'serial_number'     => 'nullable|string|max:100',
            'manufacturer'      => 'nullable|string|max:255',
            'model'             => 'nullable|string|max:255',
            'category_id'       => 'nullable|uuid|exists:categories,id',
            'location_id'       => 'nullable|string|max:255',
            'purchase_date'     => 'nullable|date',
            'purchase_cost'     => 'nullable|numeric|min:0',
            'current_value'     => 'nullable|numeric|min:0',
            'estimated_value'   => 'nullable|numeric|min:0',
            'description'       => 'nullable|string|max:2000',
            'status'            => 'nullable|in:Ordered,Received,Active,Under Maintenance,Retired,Disposed',
        ]);

        // Resolve location
        $locationId = $data['location_id'] ?? null;
        if ($locationId && !Str::isUuid($locationId)) {
            $name       = trim($locationId);
            $loc        = Location::whereRaw('LOWER(name) = ?', [strtolower($name)])->first()
                       ?? Location::create(['name' => $name, 'address' => $name]);
            $locationId = $loc->id;
        }

        Asset::create([
            'name'            => $data['name'],
            'serial_number'   => $data['serial_number'] ?? 'ASSET-' . strtoupper(Str::random(8)),
            'manufacturer'    => $data['manufacturer'] ?? null,
            'model'           => $data['model'] ?? null,
            'category_id'     => $data['category_id'] ?? null,
            'location_id'     => $locationId,
            'purchase_date'   => $data['purchase_date'] ?? null,
            'purchase_cost'   => $data['purchase_cost'] ?? 0,
            'current_value'   => $data['current_value'] ?? $data['estimated_value'] ?? 0,
            'estimated_value' => $data['estimated_value'] ?? $data['purchase_cost'] ?? 0,
            'description'     => $data['description'] ?? null,
            'status'          => $data['status'] ?? 'Active',
            'organization_id' => $user->organization_id,
            'created_by'      => $user->id,
        ]);

        return redirect()->route('ceo.inventory')->with('success', 'Asset added to inventory!');
    }

    // ─────────────────────────────────────────────────────
    // CEO: Asset Request Queue (approve / reject / assign)
    // ─────────────────────────────────────────────────────

    public function ceoRequests()
    {
        $user = auth()->user();
        if (!$user->isExecutive()) return redirect()->route($user->getDashboardRoute());

        $org = $user->organization;

        $pendingRequests = AssetRequest::where('organization_id', $org->id)
            ->where('status', 'pending')
            ->with('requestedBy')
            ->orderByDesc('created_at')
            ->get();

        $reviewedRequests = AssetRequest::where('organization_id', $org->id)
            ->whereIn('status', ['approved', 'rejected', 'fulfilled'])
            ->with('requestedBy', 'approvedBy')
            ->orderByDesc('reviewed_at')
            ->limit(30)
            ->get();

        $availableAssets = Asset::where('organization_id', $org->id)
            ->where('status', 'Active')
            ->doesntHave('currentAssignment')
            ->with('category')
            ->orderBy('name')
            ->get();

        $staffMembers = User::where('organization_id', $org->id)
            ->where('role', 'staff')
            ->orderBy('name')
            ->get();

        return view('workflow.ceo-requests', compact(
            'pendingRequests', 'reviewedRequests', 'availableAssets', 'staffMembers'
        ));
    }

    public function ceoApprove(Request $request, AssetRequest $assetRequest)
    {
        $user = auth()->user();
        if (!$user->isExecutive()) return redirect()->route($user->getDashboardRoute());

        $request->validate([
            'asset_id'         => 'required|uuid|exists:assets,id',
            'approval_notes'   => 'nullable|string|max:500',
        ]);

        $asset = Asset::findOrFail($request->asset_id);

        // Approve the request
        $assetRequest->approve($user, $request->approval_notes);

        // Create the assignment
        AssetAssignment::create([
            'asset_id'          => $asset->id,
            'organization_id'   => $user->organization_id,
            'asset_request_id'  => $assetRequest->id,
            'assigned_to'       => $assetRequest->requested_by,
            'assigned_by'       => $user->id,
            'quantity'          => $assetRequest->quantity,
            'status'            => 'assigned',
            'assignment_notes'  => $request->approval_notes,
            'assigned_at'       => now(),
        ]);

        // Mark request as fulfilled
        $assetRequest->update(['status' => 'fulfilled', 'fulfilled_at' => now()]);

        // Update asset status
        $asset->update(['status' => 'Active']);

        return redirect()->route('ceo.requests')->with('success', "Request approved and {$asset->name} assigned to {$assetRequest->requestedBy->name}.");
    }

    public function ceoReject(Request $request, AssetRequest $assetRequest)
    {
        $user = auth()->user();
        if (!$user->isExecutive()) return redirect()->route($user->getDashboardRoute());

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $assetRequest->reject($user, $request->rejection_reason);

        return redirect()->route('ceo.requests')->with('success', 'Request rejected.');
    }

    // ─────────────────────────────────────────────────────
    // CEO: Condition Reports (repair needed / stolen)
    // ─────────────────────────────────────────────────────

    public function ceoReports()
    {
        $user = auth()->user();
        if (!$user->isExecutive()) return redirect()->route($user->getDashboardRoute());

        $org = $user->organization;

        $reports = AssetConditionReport::where('organization_id', $org->id)
            ->with('asset', 'reportedBy', 'assetAssignment')
            ->orderByDesc('created_at')
            ->paginate(25);

        $unreviewedCount = AssetConditionReport::where('organization_id', $org->id)
            ->whereNull('reviewed_at')
            ->count();

        return view('workflow.ceo-reports', compact('reports', 'unreviewedCount'));
    }

    public function ceoMarkReviewed(Request $request, AssetConditionReport $report)
    {
        $user = auth()->user();
        if (!$user->isExecutive()) return redirect()->route($user->getDashboardRoute());

        $request->validate(['review_notes' => 'nullable|string|max:500']);

        $report->update([
            'reviewed_by'   => $user->id,
            'reviewed_at'   => now(),
            'review_notes'  => $request->review_notes,
        ]);

        // If stolen/damaged — update asset status
        if (in_array($report->condition, ['stolen', 'damaged', 'broken'])) {
            $report->asset?->update(['status' => ucfirst($report->condition)]);
        }
        if ($report->condition === 'needs_repair') {
            $report->asset?->update(['status' => 'Under Maintenance']);
        }

        return redirect()->route('ceo.reports')->with('success', 'Report marked as reviewed.');
    }

    // ─────────────────────────────────────────────────────
    // STAFF: Request an Asset
    // ─────────────────────────────────────────────────────

    public function staffRequestForm()
    {
        $user = auth()->user();
        if (!$user->isStaff()) return redirect()->route($user->getDashboardRoute());

        $categories = Category::orderBy('name')->get();
        $locations  = Location::orderBy('name')->get();

        return view('workflow.staff-request', compact('categories', 'locations'));
    }

    public function staffSubmitRequest(Request $request)
    {
        $user = auth()->user();
        if (!$user->isStaff()) return redirect()->route($user->getDashboardRoute());

        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'asset_type'   => 'required|string|max:255',
            'quantity'     => 'required|integer|min:1|max:100',
            'purpose'      => 'required|string|max:1000',
            'use_location' => 'required|string|max:255',
            'description'  => 'nullable|string|max:1000',
        ]);

        AssetRequest::create([
            'organization_id' => $user->organization_id,
            'requested_by'    => $user->id,
            'title'           => $data['title'],
            'asset_type'      => $data['asset_type'],
            'quantity'        => $data['quantity'],
            'purpose'         => $data['purpose'],
            'use_location'    => $data['use_location'],
            'description'     => $data['description'] ?? '',
            'status'          => 'pending',
            'requested_at'    => now(),
        ]);

        return redirect()->route('staff.requests')->with('success', 'Your request has been submitted. The CEO will review it shortly.');
    }

    public function staffMyRequests()
    {
        $user = auth()->user();
        if (!$user->isStaff()) return redirect()->route($user->getDashboardRoute());

        $requests = AssetRequest::where('requested_by', $user->id)
            ->with('approvedBy')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('workflow.staff-my-requests', compact('requests'));
    }

    // ─────────────────────────────────────────────────────
    // STAFF: Leave Requests
    // ─────────────────────────────────────────────────────

    public function staffLeaveForm()
    {
        $user = auth()->user();
        if (!$user->isStaff()) return redirect()->route($user->getDashboardRoute());

        $myLeaves = LeaveRequest::where('requested_by', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('workflow.staff-leave', compact('myLeaves'));
    }

    public function staffSubmitLeave(Request $request)
    {
        $user = auth()->user();
        if (!$user->isStaff()) return redirect()->route($user->getDashboardRoute());

        $data = $request->validate([
            'leave_type' => 'required|in:annual,sick,emergency,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:1000',
        ]);

        LeaveRequest::create([
            'organization_id' => $user->organization_id,
            'requested_by'    => $user->id,
            'leave_type'      => $data['leave_type'],
            'start_date'      => $data['start_date'],
            'end_date'        => $data['end_date'],
            'reason'          => $data['reason'],
            'status'          => 'pending',
        ]);

        return redirect()->route('staff.leave')->with('success', 'Leave request submitted. The CEO will review it shortly.');
    }

    // ─────────────────────────────────────────────────────
    // STAFF: Asset Report (stolen / needs repair / outdated)
    // ─────────────────────────────────────────────────────

    public function staffReportForm()
    {
        $user = auth()->user();
        if (!$user->isStaff()) return redirect()->route($user->getDashboardRoute());

        $myAssignments = AssetAssignment::where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'in_use'])
            ->with('asset', 'asset.category')
            ->get();

        $myReports = AssetConditionReport::where('reported_by', $user->id)
            ->with('asset')
            ->orderByDesc('created_at')
            ->get();

        return view('workflow.staff-asset-report', compact('myAssignments', 'myReports'));
    }

    public function staffSubmitAssetReport(Request $request)
    {
        $user = auth()->user();
        if (!$user->isStaff()) return redirect()->route($user->getDashboardRoute());

        $data = $request->validate([
            'asset_assignment_id' => 'required|uuid|exists:asset_assignments,id',
            'condition'           => 'required|in:needs_repair,stolen,outdated,damaged',
            'description'         => 'required|string|max:1000',
        ]);

        $assignment = AssetAssignment::where('id', $data['asset_assignment_id'])
            ->where('assigned_to', $user->id)
            ->firstOrFail();

        AssetConditionReport::create([
            'asset_assignment_id'       => $assignment->id,
            'asset_id'                  => $assignment->asset_id,
            'organization_id'           => $user->organization_id,
            'reported_by'               => $user->id,
            'condition'                 => $data['condition'],
            'description'               => $data['description'],
            'status'                    => 'pending',
            'requires_urgent_attention' => in_array($data['condition'], ['stolen', 'damaged']),
            'reported_at'               => now(),
        ]);

        return redirect()->route('staff.report')->with('success', 'Asset report submitted. The CEO will be notified.');
    }

    // ─────────────────────────────────────────────────────
    // STAFF: Asset Registry (what staff sees from CEO inventory)
    // ─────────────────────────────────────────────────────

    public function staffAssetRegistry()
    {
        $user = auth()->user();
        if (!$user->isStaff()) return redirect()->route($user->getDashboardRoute());

        $org = $user->organization;

        // All assets the org has (CEO's inventory)
        $availableAssets = Asset::where('organization_id', $org->id)
            ->where('status', 'Active')
            ->with('category', 'location')
            ->orderBy('name')
            ->get();

        // Assets currently assigned to this staff member
        $myAssignments = AssetAssignment::where('assigned_to', $user->id)
            ->whereIn('status', ['assigned', 'in_use'])
            ->with('asset', 'asset.category', 'asset.location')
            ->get();

        // My pending/approved requests
        $myRequests = AssetRequest::where('requested_by', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('workflow.staff-asset-registry', compact('availableAssets', 'myAssignments', 'myRequests'));
    }

    // ─────────────────────────────────────────────────────
    // CEO: Leave Request Review
    // ─────────────────────────────────────────────────────

    public function ceoLeaveRequests()
    {
        $user = auth()->user();
        if (!$user->isExecutive()) return redirect()->route($user->getDashboardRoute());

        $org = $user->organization;

        $pending = LeaveRequest::where('organization_id', $org->id)
            ->where('status', 'pending')
            ->with('requestedBy')
            ->orderByDesc('created_at')
            ->get();

        $reviewed = LeaveRequest::where('organization_id', $org->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->with('requestedBy', 'reviewedBy')
            ->orderByDesc('reviewed_at')
            ->limit(30)
            ->get();

        return view('workflow.ceo-leave', compact('pending', 'reviewed'));
    }

    public function ceoApproveLeave(Request $request, LeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        if (!$user->isExecutive()) return redirect()->route($user->getDashboardRoute());

        $request->validate(['review_notes' => 'nullable|string|max:500']);

        $leaveRequest->update([
            'status'       => 'approved',
            'reviewed_by'  => $user->id,
            'reviewed_at'  => now(),
            'review_notes' => $request->review_notes,
        ]);

        return redirect()->route('ceo.leave')->with('success', 'Leave request approved.');
    }

    public function ceoRejectLeave(Request $request, LeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        if (!$user->isExecutive()) return redirect()->route($user->getDashboardRoute());

        $request->validate(['review_notes' => 'required|string|max:500']);

        $leaveRequest->update([
            'status'       => 'rejected',
            'reviewed_by'  => $user->id,
            'reviewed_at'  => now(),
            'review_notes' => $request->review_notes,
        ]);

        return redirect()->route('ceo.leave')->with('success', 'Leave request rejected.');
    }
}
