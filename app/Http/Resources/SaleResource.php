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
            'sale_number' => $this->sale_number,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'shop_id' => $this->shop_id,
            'customer' => new UserResource($this->whenLoaded('customer')),
            'customer_id' => $this->customer_id,
            'reservation' => new ReservationResource($this->whenLoaded('reservation')),
            'reservation_id' => $this->reservation_id,
            'cashier' => new UserResource($this->whenLoaded('cashier')),
            'cashier_id' => $this->cashier_id,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'amount_tendered' => (float) $this->amount_tendered,
            'change' => (float) $this->change,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'total_quantity' => $this->whenLoaded('items', fn () => $this->items->sum('quantity')),
            'completed_at' => $this->completed_at?->toISOString(),
            'voided_at' => $this->voided_at?->toISOString(),
            'voided_by' => new UserResource($this->whenLoaded('voidedBy')),
            'void_reason' => $this->void_reason,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
