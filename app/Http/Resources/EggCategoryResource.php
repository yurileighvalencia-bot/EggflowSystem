<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EggCategoryResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'default_price' => (float) $this->default_price,
            'low_stock_threshold' => $this->low_stock_threshold,
            'restock_quantity' => $this->restock_quantity,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'is_tax_exempt' => (bool) ($this->is_tax_exempt ?? false),
            'batches_count' => $this->whenCounted('batches'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
