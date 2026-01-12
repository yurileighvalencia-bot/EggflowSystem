<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
            'sale_code' => $this->sale_code,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'shop_id' => $this->shop_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'customer_id' => $this->customer_id,
            'is_walk_in' => is_null($this->customer_id),
            'reservation' => new ReservationResource($this->whenLoaded('reservation')),
            'reservation_id' => $this->reservation_id,
            'staff' => new UserResource($this->whenLoaded('staff')),
            'staff_id' => $this->staff_id,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'sold_at' => $this->sold_at?->toISOString(),
            'notes' => $this->notes,
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'total_quantity' => $this->whenLoaded('items', fn () => $this->items->sum('quantity')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
