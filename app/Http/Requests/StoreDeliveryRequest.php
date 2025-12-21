<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('dispatch-delivery');
    }

    public function rules(): array
    {
        return [
            'restock_request_id' => ['required', 'exists:restock_requests,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.batch_id' => ['required', 'exists:batches,id'],
            'items.*.egg_category_id' => ['required', 'exists:egg_categories,id'],
            'items.*.quantity_sent' => ['required', 'integer', 'min:1'],
            'vehicle_number' => ['nullable', 'string', 'max:20'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one delivery item is required.',
            'items.*.batch_id.required' => 'Each item must have a batch.',
            'items.*.quantity_sent.min' => 'Quantity sent must be at least 1.',
        ];
    }
}
