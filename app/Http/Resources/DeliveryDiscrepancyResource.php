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
            'discrepancy_type' => $this->discrepancy_type,
            'quantity_expected' => $this->quantity_expected,
            'quantity_received' => $this->quantity_received,
            'quantity_difference' => $this->quantity_expected - $this->quantity_received,
            'reason' => $this->reason,
            'status' => $this->status,
            'reported_by' => new UserResource($this->whenLoaded('reportedBy')),
            'reported_at' => $this->reported_at?->toISOString(),
            'investigated_by' => new UserResource($this->whenLoaded('investigatedBy')),
            'investigated_at' => $this->investigated_at?->toISOString(),
            'investigation_notes' => $this->investigation_notes,
            'resolution' => $this->resolution,
            'resolution_notes' => $this->resolution_notes,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'wastage_log' => new WastageLogResource($this->whenLoaded('wastageLog')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
