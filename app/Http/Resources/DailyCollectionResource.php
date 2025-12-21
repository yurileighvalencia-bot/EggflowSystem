<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyCollectionResource extends JsonResource
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
            'collection_date' => $this->collection_date?->toDateString(),
            'farm' => new FarmResource($this->whenLoaded('farm')),
            'farm_id' => $this->farm_id,
            'egg_category' => new EggCategoryResource($this->whenLoaded('eggCategory')),
            'egg_category_id' => $this->egg_category_id,
            'batch' => new BatchResource($this->whenLoaded('batch')),
            'batch_id' => $this->batch_id,
            'quantity_collected' => $this->quantity_collected,
            'damaged_quantity' => $this->damaged_quantity,
            'net_quantity' => $this->quantity_collected - $this->damaged_quantity,
            'collected_by' => new UserResource($this->whenLoaded('collectedBy')),
            'notes' => $this->notes,
            'revisions' => DailyCollectionRevisionResource::collection($this->whenLoaded('revisions')),
            'revisions_count' => $this->whenCounted('revisions'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
