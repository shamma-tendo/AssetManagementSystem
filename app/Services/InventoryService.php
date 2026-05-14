<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SparePart;

class InventoryService
{
    public function create(array $data): SparePart
    {
        return SparePart::create($data);
    }

    public function update(SparePart $part, array $data): SparePart
    {
        $part->update($data);

        return $part;
    }

    public function addStock(SparePart $part, int $quantity): SparePart
    {
        $part->stock_quantity += $quantity;
        $part->save();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'spare_part.stock_added',
            'model_type' => SparePart::class,
            'model_id' => $part->id,
            'changes' => ['quantity_added' => $quantity],
        ]);

        return $part;
    }

    public function removeStock(SparePart $part, int $quantity): SparePart
    {
        if ($part->stock_quantity < $quantity) {
            throw new \Exception('Insufficient stock');
        }

        $part->stock_quantity -= $quantity;
        $part->save();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'spare_part.stock_removed',
            'model_type' => SparePart::class,
            'model_id' => $part->id,
            'changes' => ['quantity_removed' => $quantity],
        ]);

        return $part;
    }

    public function getLowStockParts()
    {
        return SparePart::whereRaw('stock_quantity <= reorder_point')
            ->with(['category', 'location'])
            ->get();
    }

    public function getInventoryValue(): float
    {
        return SparePart::selectRaw('SUM(stock_quantity * unit_cost) as total')
            ->first()
            ->total ?? 0;
    }

    public function getAllParts(array $filters = [])
    {
        $query = SparePart::query();

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['low_stock'])) {
            $query->whereRaw('stock_quantity <= reorder_point');
        }

        if (isset($filters['out_stock'])) {
            $query->where('stock_quantity', 0);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('part_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('part_number', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->with(['category', 'location'])
            ->orderBy('part_name', 'asc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getInventoryStats(): array
    {
        $lowStock = SparePart::whereRaw('stock_quantity <= reorder_point')->count();
        $outOfStock = SparePart::where('stock_quantity', 0)->count();

        return [
            'total_parts' => SparePart::count(),
            'low_stock_parts' => $lowStock,
            'out_of_stock_parts' => $outOfStock,
            'inventory_value' => $this->getInventoryValue(),
        ];
    }
}
