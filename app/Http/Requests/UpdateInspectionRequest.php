<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => 'sometimes|uuid|exists:assets,id',
            'inspection_type' => 'sometimes|string|max:255',
            'compliance_standard' => 'nullable|string|max:255',
            'scheduled_date' => 'sometimes|date_format:Y-m-d H:i:s',
            'next_due_date' => 'nullable|date_format:Y-m-d H:i:s',
            'inspector_id' => 'sometimes|uuid|exists:users,id',
            'findings' => 'nullable|string',
            'corrective_actions' => 'nullable|string',
            'certification_number' => 'nullable|string',
            'certification_expiry' => 'nullable|date',
        ];
    }
}
