<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestockRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('request-restock');
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'exists:shops,id'],
            'egg_category_id' => ['required', 'exists:egg_categories,id'],
            'quantity_requested' => ['required', 'integer', 'min:1', 'max:50000'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_id.required' => 'Please select a shop.',
            'egg_category_id.required' => 'Please select an egg category.',
            'quantity_requested.min' => 'Requested quantity must be at least 1.',
        ];
    }
}
