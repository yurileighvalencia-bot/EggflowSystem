<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receive-delivery');
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.delivery_item_id' => ['required', 'exists:delivery_items,id'],
            'items.*.qty_received' => ['required', 'integer', 'min:0'],
            'items.*.qty_rejected' => ['nullable', 'integer', 'min:0'],
            'items.*.rejection_reason' => ['required_if:items.*.qty_rejected,>,0', 'nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.rejection_reason.required_if' => 'Please provide a reason for rejected items.',
            'items.*.qty_received.required' => 'Please specify quantity received for each item.',
        ];
    }
}
