<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-sale');
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'exists:shops,id'],
            'customer_id' => ['nullable', 'exists:users,id'],
            'reservation_id' => ['nullable', 'exists:reservations,id'],
            'payment_method' => ['required', Rule::in(Sale::PAYMENT_METHODS)],
            'items' => ['required_without:reservation_id', 'array', 'min:1'],
            'items.*.egg_category_id' => ['required_with:items', 'exists:egg_categories,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:10000'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required_without' => 'Items are required when not fulfilling a reservation.',
            'payment_method.in' => 'Invalid payment method.',
        ];
    }
}
