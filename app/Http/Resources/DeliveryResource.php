<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
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
            'delivery_number' => $this->delivery_number,
            'restock_request' => new RestockRequestResource($this->whenLoaded('restockRequest')),
            'restock_request_id' => $this->restock_request_id,
            'farm' => new FarmResource($this->whenLoaded('farm')),
            'farm_id' => $this->farm_id,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'shop_id' => $this->shop_id,
            'status' => $this->status,
            'dispatched_at' => $this->dispatched_at?->toISOString(),
            'dispatched_by' => new UserResource($this->whenLoaded('dispatchedBy')),
            'received_at' => $this->received_at?->toISOString(),
            'received_by' => new UserResource($this->whenLoaded('receivedBy')),
            'expected_arrival' => $this->expected_arrival?->toISOString(),
            'vehicle_info' => $this->vehicle_info,
            'driver_name' => $this->driver_name,
            'driver_phone' => $this->driver_phone,
            'notes' => $this->notes,
            'items' => DeliveryItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'discrepancies' => DeliveryDiscrepancyResource::collection($this->whenLoaded('discrepancies')),
            'has_discrepancies' => $this->whenLoaded('discrepancies', fn () => $this->discrepancies->isNotEmpty()),
            'total_quantity_sent' => $this->whenLoaded('items', fn () => $this->items->sum('quantity_sent')),
            'total_quantity_received' => $this->whenLoaded('items', fn () => $this->items->sum('quantity_received')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
