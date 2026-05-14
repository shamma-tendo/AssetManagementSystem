<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => 'required|uuid|exists:assets,id',
            'type' => 'required|in:Preventive,Corrective,Predictive',
            'assigned_to' => 'nullable|uuid|exists:users,id',
            'description' => 'nullable|string',
            'scheduled_date' => 'nullable|date_format:Y-m-d H:i:s',
            'estimated_labor_hours' => 'nullable|numeric|min:0',
            'estimated_cost' => 'nullable|numeric|min:0',
        ];
    }
}
