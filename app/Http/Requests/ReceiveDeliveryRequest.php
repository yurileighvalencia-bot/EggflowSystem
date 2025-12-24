<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            // Removed broken required_if - handled in withValidator()
            'items.*.rejection_reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance with custom validation logic.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                $qtyRejected = (int) ($item['qty_rejected'] ?? 0);
                $reason = $item['rejection_reason'] ?? null;

                // Business Logic: Rejection reason required when qty_rejected > 0
                if ($qtyRejected > 0 && empty($reason)) {
                    $validator->errors()->add(
                        "items.{$index}.rejection_reason",
                        "Please provide a reason for the {$qtyRejected} rejected items in line " . ($index + 1) . "."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.*.qty_received.required' => 'Please specify quantity received for each item.',
            'items.*.delivery_item_id.exists' => 'One of the delivery items is invalid.',
        ];
    }
}
