<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryItemResource extends JsonResource
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
            'delivery_id' => $this->delivery_id,
            'batch' => new BatchResource($this->whenLoaded('batch')),
            'batch_id' => $this->batch_id,
            'egg_category' => new EggCategoryResource($this->whenLoaded('eggCategory')),
            'egg_category_id' => $this->egg_category_id,
            'quantity_sent' => $this->quantity_sent,
            'quantity_received' => $this->quantity_received,
            'quantity_discrepancy' => $this->quantity_sent - ($this->quantity_received ?? 0),
            'has_discrepancy' => $this->quantity_received !== null && $this->quantity_sent !== $this->quantity_received,
            'unit_price' => (float) $this->unit_price,
            'line_total' => (float) ($this->quantity_received ?? $this->quantity_sent) * $this->unit_price,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
