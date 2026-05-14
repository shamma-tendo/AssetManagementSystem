<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => 'required|uuid|exists:assets,id',
            'inspection_type' => 'required|string|max:255',
            'compliance_standard' => 'nullable|string|max:255',
            'scheduled_date' => 'required|date_format:Y-m-d H:i:s',
            'next_due_date' => 'nullable|date_format:Y-m-d H:i:s',
            'inspector_id' => 'required|uuid|exists:users,id',
            'findings' => 'nullable|string',
            'corrective_actions' => 'nullable|string',
        ];
    }
}
