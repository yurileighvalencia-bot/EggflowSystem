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
            'qty_sent' => $this->qty_sent,
            'qty_received' => $this->qty_received,
            'qty_rejected' => $this->qty_rejected,
            'rejection_reason' => $this->rejection_reason,
            'qty_missing' => $this->missing_quantity,
            'has_discrepancy' => $this->qty_received !== null && $this->hasDiscrepancy(),
            'is_confirmed' => $this->isConfirmed(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
