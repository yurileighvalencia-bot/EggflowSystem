<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryDiscrepancyResource extends JsonResource
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
            'delivery' => new DeliveryResource($this->whenLoaded('delivery')),
            'delivery_id' => $this->delivery_id,
            'delivery_item' => new DeliveryItemResource($this->whenLoaded('deliveryItem')),
            'delivery_item_id' => $this->delivery_item_id,
            'qty_sent' => $this->qty_sent,
            'qty_received' => $this->qty_received,
            'qty_rejected' => $this->qty_rejected,
            'qty_missing' => $this->qty_missing,
            'reported_by' => new UserResource($this->whenLoaded('reporter')),
            'reported_by_id' => $this->reported_by,
            'reported_at' => $this->reported_at?->toISOString(),
            'investigated_by' => new UserResource($this->whenLoaded('investigator')),
            'investigated_by_id' => $this->investigated_by,
            'investigated_at' => $this->investigated_at?->toISOString(),
            'resolution' => $this->resolution,
            'notes' => $this->notes,
            'is_resolved' => $this->isResolved(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
