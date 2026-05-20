<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Services\AssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssetWebController extends Controller
{
    public function __construct(private AssetService $assetService) {}

    public function create(): View
    {
        return view('assets.create', [
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $asset = $this->assetService->create($request->validated());

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', 'Asset created successfully.');
    }

    public function show(Asset $asset): View
    {
        $asset->load([
            'category', 'location', 'department',
            'currentAssignment.assignedTo',
            'workOrders' => fn ($q) => $q->latest()->limit(20),
            'maintenanceRecords' => fn ($q) => $q->latest()->limit(20),
            'depreciationRecords' => fn ($q) => $q->latest('year')->limit(10),
        ]);

        return view('assets.show', compact('asset'));
    }

    public function edit(Asset $asset): View
    {
        return view('assets.edit', [
            'asset' => $asset->load(['category', 'location', 'department']),
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['status']) && $data['status'] !== $asset->status) {
            $this->assetService->changeStatus($asset, $data['status']);
            unset($data['status']);
        }

        if (! empty($data)) {
            $this->assetService->update($asset->fresh(), $data);
        }

        return redirect()
            ->route('assets.show', $asset->fresh())
            ->with('success', 'Asset updated successfully.');
    }
}
