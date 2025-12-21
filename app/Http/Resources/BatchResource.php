<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'egg_category' => new EggCategoryResource($this->whenLoaded('eggCategory')),
            'egg_category_id' => $this->egg_category_id,
            'farm' => new FarmResource($this->whenLoaded('farm')),
            'farm_id' => $this->farm_id,
            'production_date' => $this->production_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'initial_quantity' => $this->initial_quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'unit_cost' => (float) $this->unit_cost,
            'status' => $this->status,
            'days_until_expiry' => $this->expiry_date ? now()->diffInDays($this->expiry_date, false) : null,
            'is_expired' => $this->expiry_date ? $this->expiry_date->isPast() : false,
            'is_expiring_soon' => $this->expiry_date ? $this->expiry_date->diffInDays(now()) <= 3 : false,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
