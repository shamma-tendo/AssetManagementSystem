<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSparePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $partId = $this->route('sparePart')?->id;

        return [
            'part_number' => 'sometimes|string|unique:spare_parts,part_number,' . $partId . ',id',
            'part_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'supplier' => 'nullable|string|max:255',
            'unit_cost' => 'sometimes|numeric|min:0',
            'reorder_point' => 'sometimes|integer|min:0',
            'reorder_quantity' => 'sometimes|integer|min:1',
            'unit_of_measure' => 'nullable|string',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'location_id' => 'nullable|uuid|exists:locations,id',
        ];
    }
}
