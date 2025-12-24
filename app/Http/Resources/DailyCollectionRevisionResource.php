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
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'changes' => $this->getChangeSummary(),
            'reason' => $this->reason,
            'changed_by' => new UserResource($this->whenLoaded('changedByUser')),
            'changed_at' => $this->changed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
