<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'serial_number' => $this->serial_number,
            'status' => $this->status,
            'category' => $this->when($this->category, [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'location' => $this->when($this->location, [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'city' => $this->location->city,
            ]),
            'department' => $this->when($this->department, [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ]),
            'purchase_date' => $this->purchase_date->format('Y-m-d'),
            'purchase_cost' => $this->purchase_cost,
            'current_value' => $this->current_value,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'created_at' => $this->created_at->toISOString(),
            'relevance_score' => $this->when(isset($this->relevance_score), $this->relevance_score),
        ];
    }
}
