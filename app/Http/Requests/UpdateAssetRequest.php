<?php

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $locationId = $this->input('location_id');

        if ($locationId && !Str::isUuid($locationId)) {
            $name = trim($locationId);
            $loc  = Location::whereRaw('LOWER(name) = ?', [strtolower($name)])->first()
                 ?? Location::create(['name' => $name, 'address' => $name]);
            $this->merge(['location_id' => $loc->id]);
        }
    }

    public function rules(): array
    {
        $assetId = $this->route('asset')?->id;

        return [
            'name' => 'sometimes|string|max:255',
            'serial_number' => 'sometimes|string|unique:assets,serial_number,' . $assetId . ',id|max:100',
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'category_id' => 'sometimes|uuid|exists:categories,id',
            'location_id' => 'nullable|uuid|exists:locations,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'purchase_date' => 'sometimes|date',
            'purchase_cost' => 'sometimes|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_years' => 'nullable|integer|min:1|max:50',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|unique:assets,barcode,' . $assetId . ',id',
            'qr_code' => 'nullable|string|unique:assets,qr_code,' . $assetId . ',id',
            'rfid_tag' => 'nullable|string|unique:assets,rfid_tag,' . $assetId . ',id',
            'status' => 'nullable|in:Ordered,Received,Active,Under Maintenance,Retired,Disposed',
        ];
    }
}
