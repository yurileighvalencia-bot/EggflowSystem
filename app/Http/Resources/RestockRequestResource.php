<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestockRequestResource extends JsonResource
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
            'request_number' => $this->request_number,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'shop_id' => $this->shop_id,
            'farm' => new FarmResource($this->whenLoaded('farm')),
            'farm_id' => $this->farm_id,
            'egg_category' => new EggCategoryResource($this->whenLoaded('eggCategory')),
            'egg_category_id' => $this->egg_category_id,
            'quantity_requested' => $this->quantity_requested,
            'quantity_approved' => $this->quantity_approved,
            'status' => $this->status,
            'priority' => $this->priority,
            'requested_by' => new UserResource($this->whenLoaded('requestedBy')),
            'acknowledged_by' => new UserResource($this->whenLoaded('acknowledgedBy')),
            'acknowledged_at' => $this->acknowledged_at?->toISOString(),
            'notes' => $this->notes,
            'rejection_reason' => $this->rejection_reason,
            'delivery' => new DeliveryResource($this->whenLoaded('delivery')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
