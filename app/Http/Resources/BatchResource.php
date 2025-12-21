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
            'batch_code' => $this->batch_code,
            'egg_category' => new EggCategoryResource($this->whenLoaded('eggCategory')),
            'egg_category_id' => $this->egg_category_id,
            'farm' => new FarmResource($this->whenLoaded('farm')),
            'farm_id' => $this->farm_id,
            'collection_date' => $this->collection_date?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'initial_quantity' => $this->initial_quantity,
            'current_quantity' => $this->current_quantity,
            'status' => $this->status,
            'days_until_expiry' => $this->expires_at ? now()->diffInDays($this->expires_at, false) : null,
            'is_expired' => $this->expires_at ? $this->expires_at->isPast() : false,
            'is_expiring_soon' => $this->expires_at ? $this->expires_at->diffInDays(now()) <= 3 : false,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
