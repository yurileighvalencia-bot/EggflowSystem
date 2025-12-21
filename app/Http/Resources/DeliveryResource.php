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
            'restock_request' => new RestockRequestResource($this->whenLoaded('restockRequest')),
            'restock_request_id' => $this->restock_request_id,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'shop_id' => $this->shop_id,
            'status' => $this->status,
            'dispatched_at' => $this->dispatched_at?->toISOString(),
            'dispatched_by' => new UserResource($this->whenLoaded('dispatcher')),
            'dispatched_by_id' => $this->dispatched_by,
            'received_at' => $this->received_at?->toISOString(),
            'received_by' => new UserResource($this->whenLoaded('receiver')),
            'received_by_id' => $this->received_by,
            'notes' => $this->notes,
            'items' => DeliveryItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'discrepancies' => DeliveryDiscrepancyResource::collection($this->whenLoaded('discrepancies')),
            'has_discrepancies' => $this->whenLoaded('discrepancies', fn () => $this->discrepancies->isNotEmpty()),
            'total_qty_sent' => $this->whenLoaded('items', fn () => $this->items->sum('qty_sent')),
            'total_qty_received' => $this->whenLoaded('items', fn () => $this->items->sum('qty_received')),
            'total_qty_rejected' => $this->whenLoaded('items', fn () => $this->items->sum('qty_rejected')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
