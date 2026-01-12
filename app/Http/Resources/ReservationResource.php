<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
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
            'reservation_code' => $this->reservation_code,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'shop_id' => $this->shop_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'customer_id' => $this->customer_id,
            'is_anonymous' => is_null($this->customer_id),
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'pickup_date' => $this->pickup_date?->toDateString(),
            'pickup_time' => $this->pickup_time?->format('H:i'),
            'pickup_deadline' => $this->pickup_deadline?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'is_expired' => $this->isExpired(),
            'is_active' => $this->isActive(),
            'notes' => $this->notes,
            'items' => ReservationItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'total_quantity' => $this->whenLoaded('items', fn () => $this->items->sum('quantity')),
            'sale' => new SaleResource($this->whenLoaded('sale')),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancelled_by' => new UserResource($this->whenLoaded('cancelledBy')),
            'cancellation_reason' => $this->cancellation_reason,
            'expired_at' => $this->expired_at?->toISOString(),
            'reminder_sent_at' => $this->reminder_sent_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
