<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'credit_limit' => (float) $this->credit_limit,
            'current_balance' => (float) $this->current_balance,
            'available_credit' => (float) $this->available_credit,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'display_name' => $this->display_name,
            'total_purchases' => $this->whenAppended('total_purchases'),
            'purchase_count' => $this->whenAppended('purchase_count'),
            'sales' => SaleResource::collection($this->whenLoaded('sales')),
            'reservations' => ReservationResource::collection($this->whenLoaded('reservations')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
