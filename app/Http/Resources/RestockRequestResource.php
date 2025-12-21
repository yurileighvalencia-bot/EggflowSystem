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
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'shop_id' => $this->shop_id,
            'egg_category' => new EggCategoryResource($this->whenLoaded('eggCategory')),
            'egg_category_id' => $this->egg_category_id,
            'quantity_requested' => $this->quantity_requested,
            'quantity_fulfilled' => $this->quantity_fulfilled,
            'quantity_remaining' => $this->quantity_remaining,
            'status' => $this->status,
            'requested_by' => new UserResource($this->whenLoaded('requester')),
            'requested_by_id' => $this->requested_by,
            'acknowledged_by' => new UserResource($this->whenLoaded('acknowledger')),
            'acknowledged_by_id' => $this->acknowledged_by,
            'acknowledged_at' => $this->acknowledged_at?->toISOString(),
            'notes' => $this->notes,
            'deliveries' => DeliveryResource::collection($this->whenLoaded('deliveries')),
            'latest_delivery' => new DeliveryResource($this->whenLoaded('latestDelivery')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
