<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WastageLogResource extends JsonResource
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
            'quantity' => $this->quantity,
            'source' => $this->source,
            'reason' => $this->reason,
            'delivery_discrepancy' => new DeliveryDiscrepancyResource($this->whenLoaded('deliveryDiscrepancy')),
            'delivery_discrepancy_id' => $this->delivery_discrepancy_id,
            'reported_by' => new UserResource($this->whenLoaded('reportedBy')),
            'approved_by' => new UserResource($this->whenLoaded('approvedBy')),
            'approved_at' => $this->approved_at?->toISOString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
