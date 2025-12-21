<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reservation = $this->route('reservation');
        return $this->user()->can('update', $reservation);
    }

    public function rules(): array
    {
        return [
            'pickup_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'pickup_time' => ['nullable', 'date_format:H:i'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.egg_category_id' => ['required_with:items', 'exists:egg_categories,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:10000'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_date.after_or_equal' => 'Pickup date must be today or later.',
            'items.*.quantity.min' => 'Each item quantity must be at least 1.',
        ];
    }
}
