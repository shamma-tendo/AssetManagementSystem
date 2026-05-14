<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSparePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_number' => 'required|string|unique:spare_parts',
            'part_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'supplier' => 'nullable|string|max:255',
            'unit_cost' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_point' => 'required|integer|min:0',
            'reorder_quantity' => 'required|integer|min:1',
            'unit_of_measure' => 'nullable|string',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'location_id' => 'nullable|uuid|exists:locations,id',
        ];
    }
}
