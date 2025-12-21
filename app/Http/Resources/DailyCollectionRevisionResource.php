<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyCollectionRevisionResource extends JsonResource
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
            'daily_collection_id' => $this->daily_collection_id,
            'previous_quantity' => $this->previous_quantity,
            'new_quantity' => $this->new_quantity,
            'quantity_difference' => $this->new_quantity - $this->previous_quantity,
            'previous_damaged' => $this->previous_damaged,
            'new_damaged' => $this->new_damaged,
            'damaged_difference' => $this->new_damaged - $this->previous_damaged,
            'reason' => $this->reason,
            'revised_by' => new UserResource($this->whenLoaded('revisedBy')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
