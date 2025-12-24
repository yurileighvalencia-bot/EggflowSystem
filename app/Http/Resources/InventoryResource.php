<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
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
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'shop_id' => $this->shop_id,
            'batch' => new BatchResource($this->whenLoaded('batch')),
            'batch_id' => $this->batch_id,
            'egg_category' => new EggCategoryResource($this->whenLoaded('eggCategory')),
            'egg_category_id' => $this->egg_category_id,
            'available_stock' => $this->available_stock,
            'reserved_stock' => $this->reserved_stock,
            'total_stock' => $this->available_stock + $this->reserved_stock,
            'unit_price' => (float) ($this->unit_price ?? 0),
            'low_stock_threshold' => $this->eggCategory?->low_stock_threshold ?? 0,
            'is_low_stock' => $this->available_stock <= ($this->eggCategory?->low_stock_threshold ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
