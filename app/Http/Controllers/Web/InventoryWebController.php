<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSparePartRequest;
use App\Models\Category;
use App\Models\Location;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryWebController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function create(): View
    {
        return view('inventory.create', [
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    public function store(StoreSparePartRequest $request): RedirectResponse
    {
        $this->inventoryService->create($request->validated());

        return redirect()
            ->route('inventory.index')
            ->with('success', 'Spare part created successfully.');
    }
}
